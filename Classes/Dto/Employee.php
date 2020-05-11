<?php
declare(strict_types=1);

namespace PunktDe\Analytics\Dto;

/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

final class Employee
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var int
     */
    private $timetrackingId = 0;

    /**
     * @var string
     */
    private $teamName;

    /**
     * @var string
     */
    private $fullName;

    /**
     * @var array
     */
    private $statisticsPayload;

    /**
     * Employee constructor.
     * @param string $username
     */
    public function __construct(string $username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return int
     */
    public function getTimetrackingId(): int
    {
        return $this->timetrackingId;
    }

    /**
     * @param int $timetrackingId
     * @return Employee
     */
    public function setTimetrackingId(int $timetrackingId): Employee
    {
        $this->timetrackingId = $timetrackingId;
        return $this;
    }

    /**
     * @return string
     */
    public function getTeamName(): string
    {
        return $this->teamName;
    }

    /**
     * @param string $teamName
     * @return Employee
     */
    public function setTeamName(string $teamName): Employee
    {
        $this->teamName = $teamName;
        return $this;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * @param string $fullName
     * @return Employee
     */
    public function setFullName(string $fullName): Employee
    {
        $this->fullName = $fullName;
        return $this;
    }

    /**
     * @return array
     */
    public function getStatisticsPayload(): ?array
    {
        return $this->statisticsPayload;
    }

    /**
     * @param array $statisticsPayload
     * @return Employee
     */
    public function setStatisticsPayload(array $statisticsPayload): Employee
    {
        $this->statisticsPayload = $statisticsPayload;
        return $this;
    }
}
