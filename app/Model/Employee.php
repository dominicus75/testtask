<?php

namespace Application\Model;

use \Dominicus75\DataBase\Entity;

class Employee extends Entity {

    /**
     * @var array Derived properties
     */
    protected array $derived = [];


    public function __construct($confOrInstance, string $table, array $ids = array(), array $columns = array()) {
        parent::__construct($confOrInstance, $table, $ids, $columns);
        if(!empty($ids)) {
            if($this->hasRelatedEntity('salaries')) {
                $sql = "SELECT emp_no, salary, MAX(from_date) AS from_date, MAX(to_date) AS to_date ";
                $sql .= "FROM `salaries` WHERE emp_no = :emp_no";
                $statement = $this->database->prepare($sql);
                $statement->bindParam(':emp_no', $ids[0], \PDO::PARAM_INT);
                $statement->execute();
                $salary = $statement->fetch(\PDO::FETCH_ASSOC);
                $this->related['salaries']->setProperties($salary);
            }
            if($this->hasRelatedEntity('dept_emp')) {
                $sql = "SELECT emp_no, dept_no, MAX(from_date) AS from_date, MAX(to_date) AS to_date ";
                $sql .= "FROM `dept_emp` WHERE emp_no = :emp_no";
                $statement = $this->database->prepare($sql);
                $statement->bindParam(':emp_no', $ids[0], \PDO::PARAM_INT);
                $statement->execute();
                $dept_emp = $statement->fetch(\PDO::FETCH_ASSOC);
                $this->related['dept_emp']->setProperties($dept_emp);
            }
            if($this->hasRelatedEntity('dept_manager')) {
                $sql = "SELECT emp_no, dept_no, Max(from_date) AS from_date, MAX(to_date) AS to_date ";
                $sql .= "FROM `dept_manager` WHERE emp_no = :emp_no";
                $statement = $this->database->prepare($sql);
                $statement->bindParam(':emp_no', $ids[0], \PDO::PARAM_INT);
                $statement->execute();
                $dept_manager = $statement->fetch(\PDO::FETCH_ASSOC);
                $this->related['dept_manager']->setProperties($dept_manager);
            }
            if($this->hasRelatedEntity('titles')) {
                $sql = "SELECT emp_no, title, MAX(from_date) AS from_date, MAX(to_date) AS to_date ";
                $sql .= "FROM `titles` WHERE emp_no = :emp_no;";
                $statement = $this->database->prepare($sql);
                $statement->bindParam(':emp_no', $ids[0], \PDO::PARAM_INT);
                $statement->execute();
                $title = $statement->fetch(\PDO::FETCH_ASSOC);
                $this->related['titles']->setProperties($title);
            }
        }
    }

    /**
     * 
     * Retrieve the employee's data, in an associative array
     * @return array
     * 
     */
    public function getEmployee(): array {

        $result = $this->getProperties();
        $result['title']      = $this->related['titles']->getProperty('title');
        $sql = "SELECT `dept_name` FROM `departments` ";
        $sql .= "WHERE `dept_no` = '".$this->related['dept_emp']->getProperty('dept_no')."'";
        $statement = $this->database->query($sql);
        $result['department'] = $statement->fetchColumn();
        $result['salary']     = $this->related['salaries']->getProperty('salary');
        $sql = "SELECT `dept_name` FROM `departments` ";
        $sql .= "WHERE `dept_no` = '".$this->related['dept_manager']->getProperty('dept_no')."'";
        $statement = $this->database->query($sql);
        $result['managerOf'] = $statement->fetchColumn();
        return $result;

    }

    /**
     * 
     * Auto increment emp_no
     * @return int
     * 
     */
    private function autoIncrement(): int {
        $sql = "SELECT MAX(emp_no) FROM `employees`";
        $statement = $this->database->query($sql);
        return ($statement->fetchColumn()) + 1;
    }

    /**
     * 
     * Add a new Employee to the database
     * @param array $employee - the employee's data
     * @param string $department - assigns employee to a department (dept_no)
     * @param int $salary - let's give salary to the employee
     * @param string $title - let's give a rank to the employee
     * @param string $managedDept - dept_no of managed department (optional)
     * @return self
     * @throws \RuntimeException
     * @throws \Dominicus75\DataBase\InvalidPropertyNameException
     * 
     */
    public function create(
        array $employee,
        string $department,
        int $salary,
        string $title = 'Staff',
        string $managedDept = ''
    ): self {

        try {

            if(!isset($employee['emp_no'])) { $employee['emp_no'] = $this->autoIncrement(); }
            $this->setProperties($employee);
            if($this->insert()) {
                if(!$this->assignDepartment($department)) {
                    throw new \RuntimeException('Adding of department fails');
                }
                if(!$this->assignSalary($salary)) {
                    throw new \RuntimeException('Adding of salary fails');
                }
                if(!$this->assignTitle($title)) {
                    throw new \RuntimeException('Adding of title fails');
                }
                if(!empty($managedDept)) {
                    if(!$this->appoint($managedDept)) {
                        throw new \RuntimeException('The appoint failed');
                    }
                }
            } else {
                throw new \RuntimeException('Creating of employee fails');
            }
            return $this;

        } catch(\Dominicus75\DataBase\InvalidPropertyNameException $ipne) {
            throw $ipne;
        }

    }

    /**
     * 
     * Assign employee to a department 
     * @param string $department
     * @return bool
     * 
     */
    public function assignDepartment(string $department): bool {

        if($this->hasRelatedEntity('dept_emp')) {

            if(!$this->valueExists('dept_no', $department, 'departments')) { return false; }

            try {

                if($this->related['dept_emp']->pkValueExists([
                    'emp_no' => $this->getProperty('emp_no'), 
                    'dept_no' => $department
                    ])
                ) {
                    return true;
                }

                $this->related['dept_emp']->setProperties([
                    'emp_no' => $this->getProperty('emp_no'),
                    'dept_no' => $department,
                    'from_date' => date('Y-m-d', strtotime("now")),
                    'to_date' => '9999-01-01'
                ]);

                return $this->related['dept_emp']->insert();

            } catch(\Dominicus75\DataBase\InvalidPropertyNameException $ipne) { throw $ipne; }

        }

        return false;

    }

    /**
     * 
     * Let's give salary to the employee
     * @param int $salary - the amount of the salary
     * @return bool
     * 
     */
    public function assignSalary(int $salary, string $from = ''): bool {

        if($this->hasRelatedEntity('salaries')) {

            try {

                $from = empty($from) ? date('Y-m-d', strtotime("now")) : $from;

                if($this->related['salaries']->pkValueExists([
                    'emp_no'    => $this->getProperty('emp_no'), 
                    'from_date' => $from
                    ])
                ) {
                    return true;
                }

                $this->related['salaries']->setProperties([
                    'emp_no' => $this->getProperty('emp_no'),
                    'salary' => $salary,
                    'from_date' => $from,
                    'to_date' => '9999-01-01'
                ]);

                return $this->related['salaries']->insert();

            } catch(\Dominicus75\DataBase\InvalidPropertyNameException $ipne) { throw $ipne; }

        }

        return false;
    }

    /**
     * 
     * Let's give a rank to the employee
     * @param string $title - the title of the employee
     * @param string $from - from date
     * @return bool
     * 
     */
    public function assignTitle(string $title, string $from = ''): bool {

        if($this->hasRelatedEntity('titles')) {

            try {

                $from = empty($from) ? date('Y-m-d', strtotime("now")) : $from;

                if($this->related['titles']->pkValueExists([
                    'emp_no'    => $this->getProperty('emp_no'), 
                    'title'     => $title,
                    'from_date' => $from
                    ])
                ) {
                    return true;
                }

                $this->related['titles']->setProperties([
                    'emp_no' => $this->getProperty('emp_no'),
                    'title' => $title,
                    'from_date' => $from,
                    'to_date' => '9999-01-01'
                ]);

                return $this->related['titles']->insert();

            } catch(\Dominicus75\DataBase\InvalidPropertyNameException $ipne) { throw $ipne; }

        }

        return false;
    }

    /**
     * 
     * Let's appoint the employee
     * @param string $managedDept - the department's id, that the employee will lead
     * @param string $from - from date
     * @return bool
     * 
     */
    public function appoint(string $managedDept, string $from = ''): bool {

        if($this->hasRelatedEntity('dept_manager')) {

            try {

                $from = empty($from) ? date('Y-m-d', strtotime("now")) : $from;

                if($this->related['dept_manager']->pkValueExists([
                    'emp_no'    => $this->getProperty('emp_no'), 
                    'dept_no'   => $managedDept
                    ])
                ) {
                    return true;
                }

                $this->related['dept_manager']->setProperties([
                    'emp_no'    => $this->getProperty('emp_no'),
                    'dept_no'   => $managedDept,
                    'from_date' => $from,
                    'to_date'   => '9999-01-01'
                ]);

                return $this->related['dept_manager']->insert();

            } catch(\Dominicus75\DataBase\InvalidPropertyNameException $ipne) { throw $ipne; }

        }

        return false;
    }

}

