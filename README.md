# PDO_CRUD
 Database Wrapper Library to decrease development time 

```
$pdoObj = new PdoCrud();
``` 
 
## Create a Database Connection ##

```
$dbCred = array( __
            'dbName' => "Main_DataBase",//database name
            'username' => 'root',
            'password' => ''
            );
```

 By default the fetch result type is set to PDO::FETCH_OBJ
 to change the result type change $this->resultType Property of the PDO Class
```
$dbCred->resultType = /PDO::FETCH_ASSOC;
```
## Read (Fetch results from Database table)
```
$resultData = $pdoObj->Get(array(
                            'table' => 'products',//table name
                            'fields' => array('id', 'name'),
                            'where' => array(
                                        'id' => 20
                                        )
                            ));
```                            
## Create(Insert data into a table) 
```
$lastInsertId = $pdoObj->Set(array(
                            'table' => 'products'
                            'data' => array(
                                      'name' => 'John',
                                      'age'=> 10
                            )
                          ));
```  
## Update(Update a Value in DB)
```
$rowCount = $pdoObj->Update(array(
                            'table' => 'products',
                            'data' => array(
                                   'name' => 'James'
                                   'age' => 40
                                    ),
                            'where' => array(
                                  'name' => 'John',
                                  'age' => 10
                             ));
```
## Delete(Delete a row in DB)
```
$rowCount = $pdoObj->Delete(array(
                            'table' => 'products',
                            'where' => array(
                                  'id' => 12
                                 ) 
                            ));
