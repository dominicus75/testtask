<?php

namespace Application\Model;

use \Dominicus75\DataBase\Entity;

class Employee extends Entity {

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
     * Auto increment emp_no
     * @return int
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
     * 
     */
    public function create(
        array $employee,
        string $department,
        int $salary,
        string $title = 'Staff'
    ): bool {

        try {

            if(!isset($employee['emp_no'])) { $employee['emp_no'] = $this->autoIncrement(); }
            $this->setProperties($employee);
            if($this->insert()) {

            }

        } catch(\Dominicus75\DataBase\InvalidPropertyNameException $ipne) {
            throw $ipne;
        }
        return false;
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
            try {
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


}

