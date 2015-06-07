# PHP-Critical.Lib Readme
This library is being designed for ease of use, and speed of development.<br>
By using OOP, and daisy chaining we can create simplistic code for newbies of the language.<br>
Please Note: This library uses MySQLi.<br>

##How to use;

###Return Basic Query Result with single row;
$db->_get(table,row_id);<br>
$db->id;  // Echo the ID;
Please Note: Use $db->Column_Name to access the value for said column.

###Return Basic Query Result with multiple rows;
$db->_get(table,value,column);<br>
var_dump($db->values);  //  Each row from the database will be an index of values IF there are more than one result.<br>
Pleae Note: Use $db->values[index_number][column_name] to access the value of said column.

###Insert New Value within database;
$db->insert(table, values); //  Values are formatted as '1','2','3','4' just as they are within a MySQL Query.<br>
Please Note: This function uses daisy chaining.<br>
$db->insert($table1,$values)->insert($table2,"''," . $db->lastID); //  Inserts one row in table1, and 1 row in table two consisting of the id number of the row inserted into table1.

Created by Sarah Allen at Critical Web Solutions - http://critical.ws/
