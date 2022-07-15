<?php 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");


// Declaring constant variables which are used to connnect to the database
class Constants
{
    static $DB_SERVER="localhost";
    static $DB_NAME="dbykekjn9bfa75";
    static $USERNAME="u3bqzt2hwzuvu";
    static $PASSWORD="v07lozfloksi";

}


// This class handles the API, dbConnect is called within the selectRecord function which allows us to only open the connection when we actually need to use it. The database connection could be done outside of a function but it would remain open until manually closed leaving the connection more exposed.
class DataProcessing
{

    public function dbConnect()
    {
        $con=new mysqli(Constants::$DB_SERVER,Constants::$USERNAME,Constants::$PASSWORD,Constants::$DB_NAME);
        if($con->connect_error)
        {
            return null;
        }else
        {
            return $con;
        }
    }

    public function initialiseVariables(){

    }

    // This function pulls in the "RFID" tag from the URL and then searched for the employee it relates to whilst also checking the department allocations. These are achieved using inner joins.
    public function selectRecord()
    {
        // I like sanitizing any data that a user could input.
        $id = filter_var($_GET['cn'], FILTER_SANITIZE_STRING);
        // The specification stated that the RFID is a 32 char string, The below IF statement ensures that the program does not proceed if the user has an error with their RFID card. I do this before opening the database connection to ensure the connection isnt opened unless the parameters are met.
        if (strlen($id) < 32 && strlen($id) > 32) {
            print(json_encode(array("Error: Rescan RFID Card.")));
            exit();
        } 
        $con=$this->dbConnect();
        if($con != null)
        {
            // initialising placeholder variables after the RFID has been cleaned, checked for length and the database connection has been established.
            $departmentsArray = array();
            $staffName = NULL;
            // Selects the full name based on a correct RFID check and then the departments that they are assigned to.
            $result=$con->query("SELECT employee.id, employee.fullName, departments.departmentName FROM employee 
                INNER JOIN departmentAllocation ON departmentAllocation.employeeID 
                INNER JOIN departments ON departments.id = departmentAllocation.departmentID 
                WHERE employee.RFID = '$id'");
            if($result->num_rows>0)
            {
                // Once a record has been successfully identified create the array which will hold all of the data going back to the system.
                $returnData=array();
                while($row=$result->fetch_array())
                {
                    // An employee can have one or more departments so I stored the data inside an array.
                    array_push($departmentsArray, $row["departmentName"]);
                    $staffName = $row["fullName"];
                }
                array_push($returnData, array("full_name"=>$staffName,"department"=>$departmentsArray));
                print(json_encode(array_reverse($returnData)));
            }else
            {
                print(json_encode(array("No records found.")));
            }
            // Once data has been returned successfully close the connection. Once again this is to prevent a connection being open for longer than it needs to be.
            $con->close();

        }else{
            print(json_encode(array("Error: MYSQL connection failed.")));
        }
    }
}
$reg = new DataProcessing();
$reg->selectRecord();

?>