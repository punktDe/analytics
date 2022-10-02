<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Collections;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Doctrine\Common\Collections\ArrayCollection;
use PunktDe\Analytics\Dto\Employee;

class EmployeeCollection extends ArrayCollection
{
    /**
     * @var array
     */
    private $timetrackingEmployeeIdIndex = [];

    /**
     * @param $key
     * @return Employee
     */
    public function get($key)
    {
        return parent::get($key);
    }

    /**
     * @param Employee $employee
     */
    public function setEmployee(Employee $employee): void
    {
        $this->set($employee->getUsername(), $employee);

        if ($employee->getTimetrackingId() !== 0) {
            $this->timetrackingEmployeeIdIndex[$employee->getTimetrackingId()] = $employee->getUsername();
        }
    }

    /**
     * @param int $timeTrackingEmployeeId
     * @return Employee
     */
    public function getByTimeTrackingEmployeeId(int $timeTrackingEmployeeId): ?Employee
    {
        if (isset($this->timetrackingEmployeeIdIndex[$timeTrackingEmployeeId])) {
            return $this->get($this->timetrackingEmployeeIdIndex[$timeTrackingEmployeeId]);
        }
        return null;
    }

    /**
     * @return array
     */
    public function toPlainArray(): array
    {
        $plainArray = [];
        /**
         * @var Employee $employee
         */
        foreach ($this->toArray() as $username => $employee) {
            $plainArray[$username] = [
                'user_name' => $employee->getUsername(),
                'user_fullname' => $employee->getFullName(),
                'timetracking_employee_id' => $employee->getTimetrackingId(),
                'team_name' => $employee->getTeamName(),
                'statistics' => json_encode($employee->getStatisticsPayload())
            ];
        }

        return $plainArray;
    }
}
