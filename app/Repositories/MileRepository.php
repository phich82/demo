<?php

namespace App\Repositories;

use App\Models\Mile;
use Illuminate\Support\Facades\Cache;

class MileRepository extends EloquentRepository
{
    /**
     * get model
     *
     * @return string
     */
    public function getModel()
    {
        return Mile::class;
    }

    /**
     * get the basic setting by given id
     *
     * @param integer $id
     * @return array
     */
    public function getBasicSetting($id)
    {
        return $this->model->where('id', $id)->first();
    }

    /**
     * get the current basic setting
     *
     * @param integer|null $mileType
     * @return array
     */
    public function getCurrentSetting($mileType = null)
    {
        return $this->model->whereDate('plan_start_date', '<=', date('Y-m-d'))
            ->where('mile_type', $this->getMileType($mileType))
            ->orderByDesc('plan_start_date')
            ->first();
    }

    /**
     * get the scheduled basic setting
     *
     * @param integer|null $mileType
     * @return array
     */
    public function getScheduleSetting($mileType = null)
    {
        return $this->model->whereDate('plan_start_date', '>', date('Y-m-d'))
            ->where('mile_type', $this->getMileType($mileType))
            ->orderByDesc('plan_start_date')
            ->first();
    }

    /**
     * get all scheduled basic settings
     *
     * @param integer|null $mileType
     * @return array
     */
    public function getAllScheduleSettings($mileType = null)
    {
        return $this->model->select('id', 'plan_start_date', 'amount', 'updated_at')
            ->whereDate('plan_start_date', '>', date('Y-m-d'))
            ->where('mile_type', $this->getMileType($mileType))
            ->orderBy('plan_start_date', 'ASC')
            ->get();
    }

    /**
     * update a record
     *
     * @param integer $id
     * @param array $params
     * @return boolean
     */
    public function updateBasicSetting($id, $params)
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id && $id > 0 && is_array($params) && count($params)) {
            $record = $this->model->find($id);
            return $record ? $this->update($id, $params) : false;
        }
        return false;
    }

    /**
     * delete a record from database
     *
     * @param integer $id
     * @return boolean
     */
    public function deleteBasicSetting($id)
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id && $id > 0) {
            $record = $this->model->find($id);
            return $record ? $record->delete($id) : false;
        }
        return false;
    }

    /**
     * update multiple records at once
     *
     * @param array $params
     * @return boolean
     */
    private function updateMany($params)
    {
        if (is_array($params) && !empty($params)) {
            $errors = [];
            foreach ($params as $param) {
                if (!$this->updateBasicSetting($param['id'], $param['data'])) {
                    $errors[] = false;
                }
            }
            return count($errors) === 0;
        }
        return false;
    }

    /**
     * insert multiple records at once
     *
     * @param array $params
     * @return boolean
     */
    private function insertMany($params)
    {
        if (is_array($params) && count($params)) {
            return $this->model->insert($params);
        }
        return false;
    }

    /**
     * delete multiple records at once
     *
     * @param array $params
     * @return boolean
     */
    private function deleteMany($params)
    {
        if (is_array($params) && !empty($params)) {
            $errors = [];
            foreach ($params as $param) {
                if (!$this->deleteBasicSetting($param['id'])) {
                    $errors[] = false;
                }
            }
            return count($errors) === 0;
        }
        return false;
    }

    /**
     * update/insert/delete multiple records
     *
     * how to check duplicate:
     * - ignore the records will be deleted
     * - no any duplicates in records will be inserted into database
     * - no any duplicates in records will be updated when not checking in database yet
     * - no any duplicates of records will be updated when checking them in database (except ids updated + ids deleted)
     * - no any duplicates of records will be updated & records will be inserted
     *
     * @param array $data
     * @param string $user
     * @param integer|null $mileType
     * @return array
     */
    public function processMile($data, $user, $mileType = null)
    {
        if (empty($data)) {
            return false;
        }

        $mileType = $this->getMileType($mileType);
        $errors   = $created = $updated = $deleted = $uniqueDatesInsert = $uniqueDatesUpdate = [];

        foreach ($data as $key => $input) {
            // if any errors, exit loop
            if (count($errors)) {
                break;
            }

            $arrData = [
                'plan_start_date' => $input['date'],
                'amount'          => $input['amount'],
                'mile_type'       => $mileType,
                'created_user'    => $user,
                'updated_user'    => $user,
            ];

            // filter & check duplicate PlanStartDate
            switch ($input['status']) {
                case 1: // update
                    if (is_numeric($input['id'])) {
                        $data = array_only($arrData, ['plan_start_date', 'amount', 'updated_user']);
                        // check duplicates in all records will be updated
                        $checkedUpdate = "plan_start_date='".$data['plan_start_date']."'";
                        if (in_array($checkedUpdate, $uniqueDatesUpdate)) {
                            $errors['duplicated'] = $data['plan_start_date'];
                        } else {
                            $uniqueDatesUpdate[] = "plan_start_date='".$data['plan_start_date']."'";
                            $updated[] = ['id' => $input['id'], 'data' => $data];
                        }
                    }
                    break;
                case 2: // delete
                    if (is_numeric($input['id'])) {
                        $deleted[] = ['id' => $input['id'], 'plan_start_date' => $input['date']];
                    }
                    break;
                case 3: // insert (one or more records)
                    $data = array_only($arrData, ['plan_start_date', 'amount', 'mile_type', 'created_user']);
                    // compare with current date
                    if (strtotime($data['plan_start_date']) < strtotime(date('Y-m-d'))) {
                        $errors['lower_current_date'] = $data['plan_start_date'];
                    } else {
                        $checkedNew = "plan_start_date='".$data['plan_start_date']."'";
                        // check duplicates in all new records
                        if (in_array($checkedNew, $uniqueDatesInsert)) {
                            $errors['duplicated'] = $data['plan_start_date'];
                        } else {
                            $uniqueDatesInsert[] = "plan_start_date='".$data['plan_start_date']."'";
                            $curDate = date('Y-m-d H:i:s');
                            $data['created_at'] = $curDate;
                            $data['updated_at'] = $curDate;
                            $created[] = $data;
                        }
                    }
                    break;
                default:
                    break;
            }
        }

        // if error, return error
        if (!empty($errors)) {
            return ['type' => 'fail', 'errors' => $errors];
        }

        // get ids of records will be updated
        $exceptIDsUpdate = [];
        if (!empty($updated)) {
            $exceptIDsUpdate = array_column($updated, 'id');
        }

        // ignore PlanStartDate of the records will be deleted
        if (!empty($deleted)) {
            foreach ($deleted as $row) {
                $needle      = "plan_start_date='".$row['plan_start_date']."'";
                $indexInsert = array_search($needle, $uniqueDatesInsert);
                $indexUpdate = array_search($needle, $uniqueDatesUpdate);
                $exceptIDsUpdate[] = $row['id'];

                if ($indexInsert !== false) {
                    array_splice($uniqueDatesInsert, $indexInsert, 1);
                }

                if ($indexUpdate !== false) {
                    array_splice($uniqueDatesUpdate, $indexUpdate, 1);
                }
            }
        }

        // check duplcates in uniqueDatesInsert & uniqueDatesUpdate
        $sames = array_intersect($uniqueDatesInsert, $uniqueDatesUpdate);
        if (!empty($sames)) {
            $firstSame = explode('=', $sames[0]);
            $errors['duplicated'] = count($firstSame) === 2 ? $firstSame[1] : true;
            return ['type' => 'fail', 'errors' => $errors];
        }

        // check duplicates of records will be updated in database
        $checkDuplicateUpdate = $this->checkDuplicateUpdate($uniqueDatesUpdate, $exceptIDsUpdate, $mileType);
        if (!empty($uniqueDatesUpdate) && $checkDuplicateUpdate) {
            $errors['duplicated'] = $checkDuplicateUpdate;
            return ['type' => 'fail', 'errors' => $errors];
        }

        // check duplicate of new records in database
        $checkDuplicateInsert = $this->checkDuplicateNew($uniqueDatesInsert, $mileType);
        if (!empty($uniqueDatesInsert) && $checkDuplicateInsert) {
            $errors['duplicated'] = $checkDuplicateInsert;
            return ['type' => 'fail', 'errors' => $errors];
        }

        // delete the basic setting(s)
        if (!empty($deleted)) {
            $this->deleteMany($deleted);
        }

        // update the basic setting(s)
        if (!empty($updated)) {
            $this->updateMany($updated);
        }

        // insert the basic setting(s)
        if (!empty($created)) {
            $this->insertMany($created);
        }

        // clear cache
        $this->clearCache($mileType);

        return ['type' => 'success'];
    }

    /**
     * check duplicate in database for new records
     *
     * @param array $planStartDatesNew
     * @param integer|null $mileType
     * @return boolean|string
     */
    private function checkDuplicateNew($planStartDatesNew, $mileType = null)
    {
        if (is_array($planStartDatesNew) && count($planStartDatesNew)) {
            $whereOR  = "(".implode(' OR ', $planStartDatesNew).")";
            $row = $this->model->newQuery()
                ->where('mile_type', $this->getMileType($mileType))
                ->whereRaw($whereOR)
                ->first();
            
            return $row ? $row['plan_start_date'] : false;
        }
        return false;
    }

    /**
     * check duplicate for updating basic setting(s) except IDs
     *
     * @param array $planStartDatesUpdate
     * @param array $exceptIDs
     * @param integer|null $mileType
     * @return boolean|string
     */
    private function checkDuplicateUpdate($planStartDatesUpdate, $exceptIDs, $mileType = null)
    {
        if (is_array($planStartDatesUpdate) && count($planStartDatesUpdate)) {
            $whereOR  = "(".implode(' OR ', $planStartDatesUpdate).")";
            $row = $this->model->newQuery()
                ->where('mile_type', $this->getMileType($mileType))
                ->whereRaw($whereOR)
                ->whereNotIn('id', $exceptIDs)
                ->first();
            
            return $row ? $row['plan_start_date'] : false;
        }
        return false;
    }

    /**
     * get MileType
     *
     * @param integer|null $mileType
     * @return integer
     */
    private function getMileType($mileType = null)
    {
        return !empty($mileType) ? $mileType : \Constant::MILE_ACCUMULATION;
    }

    /**
     * Get basic mile
     * @param int $mode
     * @param string $date
     * @return float
     */
    public function findByModeAndDate($mode, $date)
    {
        $cacheKey = sprintf('%s.%s.%s', $this->model->getTable(), $mode, $date);

        return Cache::remember($cacheKey, \Constant::COMMON_CACHE_EXPIRE, function () use ($mode, $date) {
            $result = $this->model->newQuery()
                ->where('mile_type', $mode)
                ->whereDate('plan_start_date', '<=', $date)
                ->orderByDesc('plan_start_date')
                ->first();

            return $result ? $result->amount : 1;
        });
    }

    /**
     * Clear cache
     *
     * @param integer $mileType
     * @return void
     */
    private function clearCache($mileType)
    {
        $cacheKey = sprintf('%s.%s.%s', $this->model->getTable(), $mileType, date('Y-m-d'));
        Cache::forget($cacheKey);
    }
}
