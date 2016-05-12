<?php

/*
 * The MIT License
 *
 * Copyright 2015 Cecil Medara.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Description of PdoCrud
 *  PDO Basic DataBase Wrapper for rapid development 
 * 
 * @author Cecil Medara
 */

namespace App\Crud
{

    class PdoCrud
    {

        /** @var string */
        public $conn;

        /** @var string */
        private $stmt;

        /** @var string */
        private $resultsType;

        /**  @var string */
        private $logfile;

        /**
         * ************************************************************************************************************************
         *                                                      Init 
         * ************************************************************************************************************************
         * 
         * @param type $cred
         * array
         *  {
         *      'dbName' => 'test',
         *      'username' => 'root',
         *      'password' => 'password'
         *  }
         * ***********************************************************************************************************************
         */
        public function __construct($cred)
        {
            try
            {

                error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR);
                $this->logfile = __DIR__ . '\PDOError.txt';
                $this->conn = new \PDO("mysql:host=localhost;dbname={$cred['dbName']}", $cred['username'], $cred['password']);
                $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);


                $this->resultsType = \PDO::FETCH_OBJ;
            }
            catch (\PDOException $pEx)
            {
                $this->Pdo_Error_Log($pEx);
                die('PDO ERROR:' . $pEx->getMessage());
            }
            catch (\Exception $ex)
            {
                echo 'ERROR:' . $ex->getMessage();
                $this->Error_Log_File($pEx);
            }
        }

        /**
         * ************************************************************************************************************************
         *                                                  Insert Method
         * ************************************************************************************************************************
         * @param type $data
         *  array
         *  {
         *      'table' => 'bikes',
         *      'data' =>  array
         *          (
         *               'bike' => 'hee',
         *              'name' => 'dfd'
         *          ),
         *  }
         * 
         * @return int Last Insert ID
         * ***********************************************************************************************************************
         */
        public function Set($data = NULL, $ifTran = NULL)
        {
            $feilds = array_keys($data['data']);
            $conn = null;
            $lastId = null;
            if (is_array($feilds))
            {
                $feilds = implode(', ', $feilds);
            }
            $excData = $this->Add_Colons($data['data']);
            $queryFeilds = array_keys($excData);
            if (is_array($queryFeilds))
            {
                $queryFeilds = implode(', ', $queryFeilds);
            }
            $query = "INSERT INTO {$data['table']} ({$feilds}) VALUES ({$queryFeilds})";
            try
            {
                $this->stmt = $this->conn->prepare($query);
                $this->Bind_Value($excData);
                $this->stmt->execute();
                $lastId = $this->conn->lastInsertId();
            }
            catch (\PDOException $pEx)
            {
                $this->Pdo_Error_Log($pEx);
                if ($ifTran != NULL)
                {
                    $this->Revert_Transaction();
                }
                die('PDO ERROR:' . $pEx->getMessage());
            }
            return $lastId;
        }

        /**
         * ************************************************************************************************************************
         *                                                  Retrieve Method
         * ************************************************************************************************************************
         * 
         * @param type $conditions
         * array
         * {
         *      'table' => 'customer',
         *      'feilds' => array( 'id', 'nsme'),
         *      'where' => array 
         *                      {
         *                         'id' => 1,
         *                         'name' => 'Name'
         *                      },
         *      'order' => array
         *                      {
         *                          'field' => 'id'
         *                          'type' => 'DESC'
         *                      }
         * 
         *      
         * }
         * @return Object Object of result
         * 
         * ***********************************************************************************************************************
         */
        public function Get($conditions = NULL, $ifTran = NULL)
        {
            $id = 1;
            $tempFeilds = $conditions['fields'];
            $feilds = implode(', ', $tempFeilds);
            $excData = $this->Add_Colons($conditions['where']);

            $query = "SELECT {$feilds} FROM {$conditions['table']}";
            $whereString = NULL;
            if (!empty($conditions['where']))
            {
                $wherefeilds = array_keys($conditions['where']);
                $morethnOne = 0;
                $chkAnd = count($wherefeilds) - 1;
                foreach ($wherefeilds as $value)
                {
                    $whereString .= $value . ' = :' . $value;
                    if ($chkAnd > $morethnOne)
                    {
                        $whereString .= ' AND ';
                    }
                    $morethnOne++;
                }
                $query .= " WHERE {$whereString}";
            }
            if (!empty($conditions['order']))
            {
                $orderCon = $conditions['order'];
                if (isset($orderCon['feild']))
                {
                    $query .= " ORDER BY `{$orderCon['feild']}` {$orderCon['type']}";
                }
                else
                {
                    throw new \Exception('ERROR: No Feild in Order By');
                }
            }
            try
            {
                $this->stmt = $this->conn->prepare($query);
                $this->Bind_Value($excData);
                $this->stmt->execute();
                $row = $this->stmt->fetchAll($this->resultsType);
            }
            catch (\PDOException $pEx)
            {
                $this->Pdo_Error_Log($pEx);
                if ($ifTran != NULL)
                {
                    $this->Revert_Transaction();
                }
                die('PDO ERROR:' . $pEx->getMessage());
            }
            return $row;
        }

        /**
         * 
         * ************************************************************************************************************************
         *                                                  Update Method
         * ************************************************************************************************************************
         * 
         * @param type $param
         *  array
         * {
         *      'table' => 'bikes',
         *      'data' =>  array
         *          (
         *               'bike' => 'hee',
         *              'name' => 'dfd'
         *          ),
         *      'where' => array
         *          (
         *              'id' => 1,
         *          )
         * }
         * 
         * @return int row Count
         * ***********************************************************************************************************************
         */
        public function Update($param, $ifTran = NULL)
        {
            try
            {
                $updatsSet = $this->Update_Set_Params($param['data']);
                $wherefeilds = array_keys($param['where']);
                $morethnOne = 0;
                $chkAnd = count($wherefeilds) - 1;
                foreach ($wherefeilds as $value)
                {
                    $whereString .= "`{$value}` = :{$value}";
                    if ($chkAnd > $morethnOne)
                    {
                        $whereString .= ' AND ';
                    }
                    $morethnOne++;
                }

                $excData = $this->Add_Colons($param['data']);
                $whereData = $this->Add_Colons($param['where']);
                $query = "UPDATE {$param['table']} SET {$updatsSet} WHERE {$whereString}";

                $this->stmt = $this->conn->prepare($query);
                $this->Bind_Value($excData);
                $this->Bind_Value($whereData);
                $this->stmt->execute();
            }
            catch (\PDOException $pEx)
            {
                $this->Pdo_Error_Log($pEx);
                if ($ifTran != NULL)
                {
                    $this->Revert_Transaction();
                }
                die('PDO ERROR:' . $pEx->getMessage());
            }
            return $this->stmt->rowCount();
        }

        /**
         * 
         * ************************************************************************************************************************
         *                                                  Delete Method
         * ************************************************************************************************************************
         * 
         * @param type $param
         *  array
         * {
         *      'table' => 'bikes',
         *      'where' => array
         *          (
         *              'id' => 1,
         *          )
         * }
         * 
         * @return int row Count
         * ***********************************************************************************************************************
         */
        public function Delete($param, $ifTran = NULL)
        {
            $where = $this->Update_Set_Params($param['where']);
            $data = $this->Add_Colons($param['where']);
            $query = "DELETE FROM {$param['table']} WHERE {$where}";
            try
            {
                $this->stmt = $this->conn->prepare($query);
                $this->Bind_Value($data);
                $this->stmt->execute();
            }
            catch (\PDOException $pEx)
            {
                $this->Pdo_Error_Log($pEx);
                if ($ifTran != NULL)
                {
                    $this->Revert_Transaction();
                }
                die('PDO ERROR:' . $pEx->getMessage());
            }

            return $this->stmt->rowCount();
        }

        /**
         * 
         * ************************************************************************************************************************
         *                                                  Close Connection Method
         * ************************************************************************************************************************
         */
        public function Close_Conn()
        {
            $this->conn = NULL;
            return $this->conn;
        }

        /**
         * 
         * ************************************************************************************************************************
         *                                              Transaction Begin Method
         * ************************************************************************************************************************
         */
        public function Begin_Transaction($param = null)
        {
            try
            {
                $this->conn->beginTransaction();
            }
            catch (\PDOException $ex)
            {
                die('PDO Begin Transcation Error:' . $pEx->getMessage());
            }
        }

        /**
         * 
         * ************************************************************************************************************************
         *                                              Transaction RollBack Method
         * ************************************************************************************************************************
         */
        public function Revert_Transaction($param = null)
        {
            try
            {
                $this->conn->rollBack();
            }
            catch (\PDOException $pEx)
            {
                die('PDO RollBack Transcation ERROR:' . $pEx->getMessage());
            }
        }

        /**
         * 
         * ************************************************************************************************************************
         *                                              Transaction Commit Method
         * ************************************************************************************************************************
         */
        public function Commit_Transaction($param = null)
        {
            try
            {
                $this->conn->commit();
            }
            catch (\PDOException $pEx)
            {
                die('PDO Commit Transcation ERROR:' . $pEx->getMessage());
            }
        }

        /**
         * 
         * ************************************************************************************************************************
         *                                                  Helper Method
         * ************************************************************************************************************************
         * 
         * @param string $type Fetch Type
         *      ie: 'array' or 'object'
         * 
         * ************************************************************************************************************************
         */
        public function setResultsType($type)
        {
            switch ($type)
            {
                case 'array':
                    $this->resultsType = \PDO::FETCH_ASSOC;
                    break;
                default:
                    $this->resultsType = \PDO::FETCH_OBJ;
                    break;
            }
        }

        /**
         * ************************************************************************************************************************
         *                                                  Helper Method
         * ************************************************************************************************************************ 
         * @param type $param
         * array
         *  {
         *      ':id' => $id,
         *      ':name' => $name
         *  }
         * ************************************************************************************************************************
         */
        private function Bind_Value($param)
        {
            foreach ($param as $key => $value)
            {
                $varType = gettype($value);
                switch ($varType)
                {
                    case 'integer':
                        $this->stmt->bindValue($key, $value, \PDO::PARAM_INT);
                        break;
                    default:
                        $this->stmt->bindValue($key, $value, \PDO::PARAM_STR);
                        break;
                }
            }
        }

        /**
         * ************************************************************************************************************************
         *                                                  Helper Method
         * ************************************************************************************************************************
         * @param type
         *  array
         *  {
         *      'bike' => 'Ducati',
         *      'name' => 'Cecil     
         *  }
         * 
         * @return type string
         *    data = "bike = :bike, name = :name"
         * 
         * ************************************************************************************************************************
         */
        private function Update_Set_Params($param)
        {
            $return = NULL;
            $keys = array_keys($param);
            $tempKeys = $keys;
            $combine = array_combine($keys, $tempKeys);
            foreach ($combine as $key => $value)
            {
                $return .= "`{$key}` = :{$value}, ";
            }
            $return = rtrim($return, ', ');
            return $return;
        }

        /**
         * ************************************************************************************************************************
         *                                                  Helper Method
         * ************************************************************************************************************************
         * @param type $data
         *  array
         *  {
         *      'bike' => 'Ducati',
         *      'name' => 'Cecil'
         *  }
         * @return type
         *  array
         *  {
         *      ':bike' => 'Ducati',
         *      ':name' => 'Cecil'
         *  }
         * ************************************************************************************************************************
         */
        private function Add_Colons($data)
        {
            $fliped = array_flip($data);
            foreach ($fliped as $key => $value)
            {
                $fliped[$key] = ':' . $value;
            }
            $data = array_flip($fliped);
            return $data;
        }

        public function Add_Back_Tick($array)
        {
            $returnA = [];
            foreach ($array as $value)
            {
                $temp = "`{$value}`";
                array_push($returnA, $temp);
            }
            return $returnA;
        }

        /**
         * 
         * @param type $errorInfo
         *  Writes a logfile PDOError.txt on the root directory.
         *  To chnage the file location call setLogtfile
         */
        private function Pdo_Error_Log($errorInfo = null)
        {
            $message = "";
            $message .= "\r\n----------------------- PDO WRAPPER ERROR ----------------------\r\n";
            $message .= "Timestamp: " . date("Y-m-d H:i:s", time()) . "\r\n";
            $message .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\r\n";
            $message .= "Error Message: " . $errorInfo->getMessage() . "\r\n";
            $message .= "Error Code: " . $errorInfo->getCode() . "\r\n";
            $message .= "Error File: " . $errorInfo->getFile() . "\r\n";
            $message .= "Error Line: " . $errorInfo->getLine() . "\r\n";
            $message .= "Error Trace string:\r\n " . $errorInfo->getTraceAsString() . "\r\n";
            $message .= "Error Previous:\r\n " . $errorInfo->getPrevious() . "\r\n";
            $message .= "\r\n------------------------------- EOF -----------------------------\r\n";
            if (!file_exists($this->logfile))
            {
                fopen($this->logfile, 'w');
            }
            file_put_contents($this->logfile, $message, FILE_APPEND);
        }

        /**
         * ************************************************************************************************************************
         *                                                  End of Class
         * ************************************************************************************************************************
         */
    }

}
