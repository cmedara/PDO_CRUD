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

namespace Modules\CloudCRUD
{
    require_once '/var/www/modules/Main/MainException.php';
//    require_once '/../Main/MainException.php';

    class PdoCrud
    {

        /** @var string */
        public $conn;
        
        /** @var string */
        public $query;
        
        /** @var string */
        public $wData;        
        
        /** @var string */
        public $data;

        /** @var string */
        private $stmt;

        /** @var string */
        private $resultsType;

        /**  @var string */
        private $databaseName;

        /** @var Object */
        private $mainExcp;

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
                $this->conn = new \PDO("mysql:host=localhost;dbname={$cred['dbName']}", $cred['username'], $cred['password']);
                $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $this->resultsType = \PDO::FETCH_OBJ;
                $this->databaseName = $cred['dbName'];
            }
            catch (\PDOException $pEx)
            {
                $data = $pEx->getTraceAsString() . "\n DataBaseName:" . $this->databaseName;
                $this->mainExcp = new \MainException($pEx->getMessage(), 'PDOException.log', __CLASS__, __METHOD__, $data);
                $this->mainExcp->Send_Mail();
            }
            catch (\Exception $ex)
            {
                $data = $ex->getTraceAsString() . "\n DataBaseName:" . $this->databaseName;
                $this->mainExcp = new \MainException($ex->getMessage(), 'Exception', __CLASS__, __METHOD__, $data);
                $this->mainExcp->Send_Mail();
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
            $feilds = $this->Add_Back_Tick($feilds);
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
            $this->query = $query;
            $this->data = $excData;
            try
            {
                $this->stmt = $this->conn->prepare($query);
                $this->Bind_Value($excData);
                $this->stmt->execute();
                $lastId = $this->conn->lastInsertId();
                $this->Stmt_NULL();
            }
            catch (\PDOException $pEx)
            {

                $data = $query . "\n DataBaseName:" . $this->databaseName;
                $this->mainExcp = new \MainException($pEx->getMessage(), 'PDOException', __CLASS__, __METHOD__, $query);
                $this->mainExcp->Send_Mail();

                if ($ifTran != NULL)
                {
                    $this->Revert_Transaction();
                }
                echo $data . '<br />';
                var_dump($pEx);
            }
            catch (\Exception $ex)
            {
                $data = $ex->getTraceAsString() . "\n DataBaseName:" . $this->databaseName;
                $this->mainExcp = new \MainException($ex->getMessage(), 'Exception', __CLASS__, __METHOD__, $query);
                $this->mainExcp->Send_Mail();
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
         *      'like' => array
         *                      {
         *                          'feild' => Patern
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
            $excData = null;
            try
            {
                $tempFeilds = $conditions['fields'];
                $tempFeilds = $this->Add_Back_Tick($tempFeilds);
                $feilds = implode(', ', $tempFeilds);


                $query = "SELECT {$feilds} FROM {$conditions['table']}";
                $whereString = NULL;
                if (!empty($conditions['where']) && empty($conditons['LIKE']))
                {
                    $excData = $this->Add_Colons($conditions['where']);
                    $wherefeilds = array_keys($conditions['where']);
                    $morethnOne = 0;
                    $chkAnd = count($wherefeilds) - 1;
                    foreach ($wherefeilds as $value)
                    {
                        $whereString .= "`{$value}`" . ' = :' . $value;
                        if ($chkAnd > $morethnOne)
                        {
                            $whereString .= ' AND ';
                        }
                        $morethnOne++;
                    }
                    $query .= " WHERE {$whereString}";
                }
                /** duplicate Code * */
                if (!empty($conditions['LIKE']) && empty($conditions['where']))
                {
                    $excData = $this->Add_Colons($conditions['LIKE']);
                    $wherefeilds = array_keys($conditions['LIKE']);
                    $morethnOne = 0;
                    $chkAnd = 0;
                    $chkAnd = count($wherefeilds) - 1;
                    foreach ($wherefeilds as $value)
                    {
                        $whereString .= "`{$value}`" . ' LIKE :' . $value;
                        if ($chkAnd > $morethnOne)
                        {
                            $whereString .= ' OR ';
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
                if (!empty($conditions['LIMIT']))
                {
                    $limitCon = $conditions['LIMIT'];
                    if (isset($limitCon['value']))
                    {
                        $query .= " LIMIT {$limitCon['value']}";
                        if(isset($limitCon['value2']))
                        {
                            $query .= ", {$limitCon['value2']}";
                        }
                    }
                    else
                    {
                        throw new \Exception('ERROR: No value in Limit By');
                    }
                    
                }
                $this->query = $query;
                $this->data = $excData;
                $this->stmt = $this->conn->prepare($query);
                $this->Bind_Value($excData);
                $this->stmt->execute();
                $row = $this->stmt->fetchAll($this->resultsType);
                $this->Stmt_NULL();
            }
            catch (\PDOException $pEx)
            {
                $data = $query . "\n DataBaseName:" . $this->databaseName;
                $this->mainExcp = new \MainException($pEx->getMessage(), 'PDOException', __CLASS__, __METHOD__, $data);
                $this->mainExcp->Send_Mail();

                if ($ifTran != NULL)
                {
                    $this->Revert_Transaction();
                }
                echo $data . '<br />';
                var_dump($pEx);
            }
            catch (\Exception $ex)
            {
                $data = $ex->getTraceAsString() . "\n DataBaseName:" . $this->databaseName;
                $this->mainExcp = new \MainException($ex->getMessage(), 'Exception', __CLASS__, __METHOD__, $data);
                $this->mainExcp->Send_Mail();
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
                $this->query = $query;
                $this->data = $excData;
                $this->wData = $whereData;
                
                $this->stmt = $this->conn->prepare($query);
                $this->Bind_Value($excData);
                $this->Bind_Value($whereData);
                $this->stmt->execute();
            }
            catch (\PDOException $pEx)
            {
                $data = $pEx->getTraceAsString() . "\n DataBaseName:" . $this->databaseName;
                $this->mainExcp = new \MainException($pEx->getMessage(), 'PDOException', __CLASS__, __METHOD__, $data);
                $this->mainExcp->Send_Mail();

                if ($ifTran != NULL)
                {
                    $this->Revert_Transaction();
                }
            }
            catch (\Exception $ex)
            {
                $data = $ex->getTraceAsString() . "\n DataBaseName:" . $this->databaseName;
                $this->mainExcp = new \MainException($ex->getMessage(), 'Exception', __CLASS__, __METHOD__, $data);
                $this->mainExcp->Send_Mail();
            }
            return $this->stmt->rowCount();
        }

        public function Stmt_NULL()
        {
            $this->stmt = null;
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
            try
            {
                $where = $this->Update_Set_Params($param['where']);
                $data = $this->Add_Colons($param['where']);
                $query = "DELETE FROM {$param['table']} WHERE {$where}";
                $this->query = $query;
                $this->data = $excData;
                $this->stmt = $this->conn->prepare($query);
                $this->Bind_Value($data);
                $this->stmt->execute();
            }
            catch (\PDOException $pEx)
            {
                $data = $pEx->getTraceAsString() . "\n DataBaseName:" . $this->databaseName;
                $this->mainExcp = new \MainException($pEx->getMessage(), 'PDOException', __CLASS__, __METHOD__, $data);
                $this->mainExcp->Send_Mail();

                if ($ifTran != NULL)
                {
                    $this->Revert_Transaction();
                }
            }
            catch (\Exception $ex)
            {
                $data = $ex->getTraceAsString() . "\n DataBaseName:" . $this->databaseName;
                $this->mainExcp = new \MainException($ex->getMessage(), 'Exception', __CLASS__, __METHOD__, $data);
                $this->mainExcp->Send_Mail();
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
            catch (\PDOException $pEx)
            {
                $data = $pEx->getTraceAsString() . "\n DataBaseName:" . $this->databaseName;
                $this->mainExcp = new \MainException($pEx->getMessage(), 'PDOException', __CLASS__, __METHOD__, $data);
                $this->mainExcp->Send_Mail();
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
                $data = $pEx->getTraceAsString() . "\n DataBaseName:" . $this->databaseName;
                $this->mainExcp = new \MainException($pEx->getMessage(), 'PDOException', __CLASS__, __METHOD__, $data);
                $this->mainExcp->Send_Mail();
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
                $data = $pEx->getTraceAsString() . "\n DataBaseName:" . $this->databaseName;
                $this->mainExcp = new \MainException($pEx->getMessage(), 'PDOException', __CLASS__, __METHOD__, $data);
                $this->mainExcp->Send_Mail();
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
            switch (strtolower($type))
            {
                case 'array':
                    $this->resultsType = \PDO::FETCH_ASSOC;
                    break;
                default:
                    $this->resultsType = \PDO::FETCH_OBJ;
                    break;
            }
        }

        public function Add_Back_Tick($array)
        {
            $returnA = array();
            foreach ($array as $value)
            {
                $temp = "`{$value}`";
                array_push($returnA, $temp);
            }
            return $returnA;
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
              error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
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
//            var_dump($data);
//            $fliped = array_flip($data);
//            print_r($data);
//            foreach ($fliped as $key => $value)
//            {
//                $fliped[$key] = ':' . $value;
//                echo $value;
//            }
//            
//            $data = array_flip($fliped);
//            var_dump($data);
            $tempData = array();
            foreach ($data as $key => $value)
            {
                $tempData[":" . $key] = $value;
            }
            return $tempData;
        }

        /**
         * ************************************************************************************************************************
         *                                                  End of Class
         * ************************************************************************************************************************
         */
    }

}
