<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */
namespace Orangehrm\Rest\Api\Pim;

use Orangehrm\Rest\Api\Exception\RecordNotFoundException;
use Orangehrm\Rest\Api\Exception\InvalidParamException;
use Orangehrm\Rest\Api\Pim\Entity\Employee;
use Orangehrm\Rest\Api\Pim\Entity\EmployeeDependant;
use Orangehrm\Rest\Api\Pim\Entity\EmployeeContactDetail;
use Orangehrm\Rest\http\RequestParams;
use Orangehrm\Rest\Http\Request;
use Orangehrm\Rest\Http\Response;

class EmployeeService {

    /**
     * @var Request
     */
    protected $request = null;
    protected $employeeService = null;
    protected $requestParams = null ;

    /**
     * Employee constants
     */
    const PARAMETER_NAME       = "name";
    const PARAMETER_ID         = "id";
    const PARAMETER_JOB_TITLE  = "jobTitle";
    const PARAMETER_STATUS     = "status";
    const PARAMETER_UNIT       = "unit";
    const PARAMETER_SUPERVISOR = "supervisor";
    const PARAMETER_LIMIT      = 'limit';

    /**
     * @var array
     */
    protected $searchParams = array(
        self::PARAMETER_NAME => 'name',
        self::PARAMETER_ID => 'id',
        self::PARAMETER_JOB_TITLE => 'jobTitle',
        self::PARAMETER_STATUS => 'status',
        self::PARAMETER_UNIT => 'unit',
        self::PARAMETER_SUPERVISOR => 'supervisor',
        self::PARAMETER_LIMIT => 'limit'
    );

    /**
     * EmployeeService constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request) {
        $this->setRequestParams(new RequestParams($request));
    }

    /**
     * @return \EmployeeService|null
     */
    protected function getEmployeeService() {

        if ($this->employeeService != null) {
            return $this->employeeService;
        } else {
            return new \EmployeeService();
        }
    }

    public function setEmployeeService($employeeService){
        $this->employeeService = $employeeService;
    }

    /**
     * @return null
     */
    public function getRequestParams() {
        return $this->requestParams;
    }

    /**
     * @param null $requestParams
     */
    public function setRequestParams($requestParams) {
        $this->requestParams = $requestParams;
    }
    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param mixed $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return Response
     * @throws RecordNotFoundException
     */
    public function getEmployeeList() {
        $employeeList = array ();
        $parameterHolder = $this->buildSearchParamHolder();
        if(empty($parameterHolder)) {
            $employeeList = $this->getEmployeeService()->getEmployeeList();
        }else{
            $employeeList = $this->getEmployeeService()->searchEmployees($parameterHolder);
        }

        if(empty($employeeList)) {
            throw new RecordNotFoundException("Employee not found");
        }

        return new Response($this->buildEmployeeData($employeeList),array());

    }

    /**
     * @return \EmployeeSearchParameterHolder|null
     */
    private function buildSearchParamHolder( ) {

        $filters = array();
        if(!empty($this->getRequestParams()->getQueryParam(self::PARAMETER_NAME))){
            $filters['employee_name'] = $this->getRequestParams()->getQueryParam(self::PARAMETER_NAME);
        }

        if(!empty($this->getRequestParams()->getQueryParam(self::PARAMETER_ID))){
            $filters['id'] = $this->getRequestParams()->getQueryParam(self::PARAMETER_ID);
        }
        if(!empty($this->getRequestParams()->getQueryParam(self::PARAMETER_JOB_TITLE))){
            $filters['job_title'] = $this->getRequestParams()->getQueryParam(self::PARAMETER_JOB_TITLE);
        }
        if(!empty($this->getRequestParams()->getQueryParam(self::PARAMETER_STATUS))){
            $filters['employee_status'] = $this->getRequestParams()->getQueryParam(self::PARAMETER_STATUS);
        }
        if(!empty($this->getRequestParams()->getQueryParam(self::PARAMETER_SUPERVISOR))){
            $filters['supervisor_name'] = $this->getRequestParams()->getQueryParam(self::PARAMETER_SUPERVISOR);
        }
        if(empty($filters)){
            return null;
        }
        $parameterHolder = new \EmployeeSearchParameterHolder();
        $parameterHolder->setFilters($filters);
        $parameterHolder->setReturnType(\EmployeeSearchParameterHolder::RETURN_TYPE_OBJECT);

        return $parameterHolder;
    }

    /**
     * @param $employeeList
     *
     * @return array
     */
    private function buildEmployeeData( $employeeList ) {
        $data = array();
        foreach ($employeeList as $employee) {
            $emp = new Employee($employee->getFirstName(), $employee->getMiddleName(), $employee->getLastName(), $employee->getEmployeeId());

            $emp->buildEmployee($employee);

            $data[] = $emp->toArray();
        }
        return $data;
    }

    /**
     * Getting employee dependants API call
     *
     * @param $request
     * @return array
     */
    public function getEmployeeDependants() {

        $responseArray = null;
        $empId = $this->getRequestParams()->getQueryParam(self::PARAMETER_ID);

        if (!is_numeric($empId)) {
            throw new InvalidParamException("Invalid Parameter");

        }
        $dependants = $this->getEmployeeService()->getEmployeeDependents($empId);

        foreach ($dependants as $dependant) {

            $empDependant = new EmployeeDependant($dependant->getName(), $dependant->getRelationship(), $dependant->getDateOfBirth());
            $responseArray[] = $empDependant->toArray();
        }
        return $responseArray;
    }

    /**
     * @return array
     * @throws RecordNotFoundException
     * @throws \HttpInvalidParamException
     */
    public function getEmployeeDetails() {

        $empId = -1;
        $employeeList [] = array();
        if (!empty($this->getRequestParams()->getQueryParam(self::PARAMETER_ID))) {
            $empId = $this->getRequestParams()->getQueryParam(self::PARAMETER_ID);
        }
        if (!is_numeric($empId)) {
            throw new InvalidParamException("Invalid Parameter");

        }
        $emp = $this->getEmployeeService()->getEmployee($empId);
        $employeeList [] = $emp;
        if (empty($employeeList) == 0) {
            throw new RecordNotFoundException("Employee not found");
        }
        return $this->buildEmployeeData($employeeList);
    }
    /**
     * get Employee contact details
     *
     * @return Response
     * @throws RecordNotFoundException
     */
    public function getEmployeeContacts() {

        $empId = -1;
        $employeeList [] = array();
        if (!empty($this->getRequestParams()->getQueryParam(self::PARAMETER_ID))) {
            $empId = $this->getRequestParams()->getQueryParam(self::PARAMETER_ID);
        }
        if (!is_numeric($empId)) {
            throw new InvalidParamException("Invalid Parameter");

        }
        // getting employee
        $emp = $this->getEmployeeService()->getEmployee($empId);

        if ($emp == null ) {
            throw new RecordNotFoundException("Contacts not found");
        }

        $empContact = new EmployeeContactDetail($emp->getFullName(),$emp->getEmployeeId());
        $empContact->buildContactDetails($emp);

        return new Response($empContact->toArray(),array());
    }

}