# PDO_CRUD
Database Wrapper Library to decrease development time
## Create a Database Connection
$dbCred = array(
            'dbName' => "Main_DataBase",
            'username' => 'root',
            'password' => ''
            );

/**
* Create a object with result type of PDO::FETCH_OBJ
* to change the result type change $this->resultType Property of the PDO Class
*/
$pdoObj = new PdoCrud();

## Read (Fetch results from Database table)
$resultData = $pdoObj->Get(array(
                            'table' => 'products',//database table name
                            'fields' => array('id', 'name'),
                            'where' => array(
                                        'id' => 20
                                        )
                            ));
## Create(insert data into a table)
$insertId = $pdoObj->Set(array(
                            'table' => 'products'
                            'data' => array(
                                      'name' => 'John',
                                      'age'=> 10
                            )
                          ));
    
