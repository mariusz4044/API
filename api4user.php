<?php

require_once 'JsonMapper.php';

class JsonInitRequest
{

    public $uid = '';
    public $pid = '';
}

class JsonGetUserRequest
{
    public $uid = '';
    public $pid = '';
    public $sid = '';
}

class JsonCreateUserRequest
{
    public $uid = '';
    public $pid = '';
    public $login = '';
    public $password = '';
    public $role = '';
}

class JsonChangeUserRequest {
    public $uid = '';
    public $pid = '';
    public $sid = '';
    public $password = '';
}

class JsonCreateUserResponse
{
    public $code = 0;
    public $msg = '';
    public $s_key = '';
}

class JsonChangeUserResponse
{
    public $code = 0;
    public $msg = '';
    public $s_password = '';
    public $clear_password = '';
}


class JsonDeleteResponse
{
    public $code = 0;
    public $msg = '';
}


class JsonScannedBarcodeRequest extends JsonInitRequest
{

    public $id = 0;
    public $barcode = '';
    public $qty = 0.0;
    public $note = '';
}

class JsonInitResponse
{

    public $code = 0;
    public $msg = '';
}

class JsonAuthResponse
{

    public $code = 0;
    public $msg = '';
    public $s_firstname = '';
    public $s_lastname = '';
    public $s_role = '';
    public $s_email = '';

}


function send_response($code, $msg)
{
    $response = new JsonInitResponse();
    $response->code = $code;
    $response->msg = $msg;
    header("Content-Type: application/json");
    echo json_encode($response);
    return;
}

const ERROR_NO_REST_JSON = 'Zapytanie nie jest w formacie REST JSON';
const ERROR_HTTP_METHOD = 'Tylko HTTP POST jest dozwolony';
const ERROR_UNKNOWN_ACTION = 'Nierozpoznano polecenia';
const ERROR_BAD_INIT_REQUEST = 'Nieprawdłowe zapytanie (init).';
const ERROR_BAD_CODE_REQUEST = 'Nieprawdłowe zapytanie (code).';
const ERROR_NO_PERMISSIONS = 'Brak uprawnień';
const ERROR_DEVICE_BLOCKED = 'Urządzenie jest niekatywne';
const ACTION_INIT = 'init';
const ACTION_CODE = 'code';

const ERROR_CONNECT_DB = 'Nie udało się połączyć z bazą danych.';
const ERROR_INVALID_PERMISSION = 'Nie posiadasz uprawnień.';
const ERROR_INVALID_DATA = 'Błędny indetyfikator użykownika (uid lub pid).';
const AUTH_SUCCESS = "Pomyślnie pobrano dane.";
const ERROR_BAD_AUTH_REQUEST = "Nieprawdłowe zapytanie (auth).";
const ERROR_BAD_GETUSER_REQUEST = "Nieprawdłowe zapytanie (getuser).";
const ERROR_INVALID_LOGIN = "Nie znaleziono użytkownika.";
const GETUSER_SCUCCESS = "Pobrano dane użytkownika pomyślnie.";
const ERROR_BAD_DELETE_REQUEST = "Nieprawdłowe zapytanie (delete).";
const DELETE_SCUCCESS = "Poprawnie usunięto użytkownika.";
const ERROR_USER_EXIST = "Użytkownik o takim loginie już istnieje.";
const SUCCESS_CREATE_ACC = "Pomyślnie stworzono konto użytkownika.";
const ERROR_CREATE_ACC = "Wystąpił błąd podczas dodawania uzytkownika.";
const ERROR_ROLE = "Podano błędną role tylko USER lub SUPERUSER.";
const CHANGE_SUCCESS = "Zmieniono hasło dla użytkownika pomyślnie.";
//Database Information
const host ="--------";
const port = "="--------";";
const user = "="--------";";
const password = "="--------";";
const dbname = "="--------";";
//


function getuser($uid,$pid,$sid){
    $conn = pg_pconnect("host=".host." port=".port." user=".user." password=".password." dbname=".dbname."");

    $res = isCorrectAuth($uid,$pid);

    if(!$res) {
        return send_response(-1,ERROR_INVALID_DATA);
    }

    $user = $res[0];

    if($user["s_role"] !== "SUPERUSER") {
        return send_response(-1, ERROR_INVALID_PERMISSION);
    }

    $qry = pg_query($conn, "SELECT * FROM t_user WHERE s_key='$sid'");
    $user_res = pg_fetch_all($qry);


    if(!$user_res) {
        return send_response(-1, ERROR_INVALID_LOGIN);
    }

    $user_res = $user_res[0];

    $response = new JsonAuthResponse();
    $response-> msg = GETUSER_SCUCCESS;
    $response-> code = 2;
    $response-> s_firstname = $user_res['s_firstname'];
    $response-> s_lastname = $user_res['s_lastname'];
    $response-> s_email = $user_res['s_email'];
    $response-> s_role = $user_res['s_role'];

    header("Content-Type: application/json");
    echo json_encode($response);

    pg_close($conn);
}

function deleteuser($uid,$pid,$sid){
    $conn = pg_pconnect("host=".host." port=".port." user=".user." password=".password." dbname=".dbname."");

    $res = isCorrectAuth($uid,$pid);

    if(!$res) {
        return send_response(-1,ERROR_INVALID_DATA);
    }

    $user = $res[0];

    if($user["s_role"] !== "SUPERUSER") {
        return send_response(-1, ERROR_INVALID_PERMISSION);
    }

    $qry = pg_query($conn, "SELECT * FROM t_user WHERE s_key='$sid'");
    $user_res = pg_fetch_all($qry);


    if(!$user_res) {
        return send_response(-1, ERROR_INVALID_LOGIN);
    }

    $qry = pg_query($conn, "DELETE FROM t_user WHERE s_key='$sid'");

    $response = new JsonAuthResponse();
    $response-> msg = DELETE_SCUCCESS;
    $response-> code = 2;

    header("Content-Type: application/json");
    echo json_encode($response);

    pg_close($conn);
}


function changepassword($uid,$pid,$sid,$password){


    $conn = pg_pconnect("host=".host." port=".port." user=".user." password=".password." dbname=".dbname."");

    $res = isCorrectAuth($uid,$pid);

    if(!$res) {
        return send_response(-1,ERROR_INVALID_DATA);
    }

    $user = $res[0];

    if($user["s_role"] !== "SUPERUSER") {
        return send_response(-1, ERROR_INVALID_PERMISSION);
    }

    $qry = pg_query($conn, "SELECT * FROM t_user WHERE s_key='$sid'");
    $user_res = pg_fetch_all($qry);

    if(!$user_res) {
        return send_response(-1, ERROR_INVALID_LOGIN);
    }

    $user_login = $user_res[0]['s_login'];

    $qry = pg_query($conn, "SELECT f_set_password('$password','$user_login')");
    $r = pg_fetch_all($qry);
    $s_pass = $r[0]['f_set_password'];
    
    $response = new JsonChangeUserResponse();
    $response-> msg = CHANGE_SUCCESS;
    $response-> code = 4;
    $response-> s_password = $s_pass;
    $response-> clear_password = $password;

    header("Content-Type: application/json");
    echo json_encode($response);

    pg_close($conn);
}

function createuser($uid,$pid,$login,$password,$role){
    if($role !== 'USER' || $role == 'SUPERUSER') {
        return send_response(-1, ERROR_ROLE);
    }

    $conn = pg_pconnect("host=".host." port=".port." user=".user." password=".password." dbname=".dbname."");

    $res = isCorrectAuth($uid,$pid);

    if(!$res) {
        return send_response(-1,ERROR_INVALID_DATA);
    }

    $user = $res[0];

    if($user["s_role"] !== "SUPERUSER") {
        return send_response(-1, ERROR_INVALID_PERMISSION);
    }

    $qry = pg_query($conn, "SELECT * FROM t_user WHERE s_login='$login'");
    $user_res = pg_fetch_all($qry);


    if($user_res) {
        return send_response(-1, ERROR_USER_EXIST);
    }

    $c_query = pg_query($conn,"INSERT INTO t_user(s_login, s_password,s_role) VALUES ('$login', '$password','$role'); SELECT s_key FROM t_user WHERE s_login='$login';");
    $res_s_key = pg_fetch_all($c_query);
    $gen_pasword = pg_query($conn,"SELECT f_set_password('$password','$login')");

    $s_key = $res_s_key[0];

    if(!$s_key['s_key']) {
        return send_response(-1, ERROR_CREATE_ACC);
    }


    $response = new JsonCreateUserResponse();
    $response-> msg = SUCCESS_CREATE_ACC;
    $response-> code = 3;
    $response-> s_key = $s_key['s_key'];

    header("Content-Type: application/json");
    echo json_encode($response);

    pg_close($conn);
}

function isCorrectAuth($uid,$pid) {
    $conn = pg_pconnect("host=".host." port=".port." user=".user." password=".password." dbname=".dbname."");

    if (!$conn) {
       return send_response(-1, ERROR_CONNECT_DB);
    }

    $qry = pg_query($conn, "SELECT * FROM t_user WHERE s_key='$uid' AND s_password='$pid'");
    $res = pg_fetch_all($qry);

    if(!$res) {
        return false;
    }

    
    pg_close($conn);
    return $res;
}


function auth($uid,$pid) {
        $res = isCorrectAuth($uid,$pid);

        if(!$res) {
            return send_response(-1,ERROR_INVALID_DATA);
        }

        $user = $res[0];

        if($user["s_role"] !== "SUPERUSER") {
            return send_response(-1, ERROR_INVALID_PERMISSION);
        }

        $response = new JsonAuthResponse();
        $response-> msg = AUTH_SUCCESS;
        $response-> code = 1;
        $response-> s_firstname = $user['s_firstname'];
        $response-> s_lastname = $user['s_lastname'];
        $response-> s_email = $user['s_email'];
        $response-> s_role = $user['s_role'];

        header("Content-Type: application/json");
        echo json_encode($response);
}


function action()
{

    $method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    if ($method !== 'POST') {
        send_response(-2, ERROR_HTTP_METHOD);
        return;
    }

    $headers = getallheaders();

    if (!array_key_exists('Content-Type', $headers)) {
        send_response(-1, ERROR_NO_REST_JSON);
        return;
    }

    if (!$headers['Content-Type'] === 'application/json') {
        send_response(-1, ERROR_NO_REST_JSON);
        return;
    }

    $json = json_decode(file_get_contents('php://input'));
    
    if ($json == null || $json == '') {
        send_response(-1, ERROR_NO_REST_JSON);
        return;
    }

    $action = filter_input(INPUT_GET, 'a');
    
    if ($action == null || $action == '') $action = ACTION_INIT;



    $mapper = new JsonMapper();
    $mapper->bExceptionOnMissingData = true;
    $mapper->bExceptionOnUndefinedProperty = true;

    switch ($action) {
        case "getuser":
            try {
            $request = $mapper->map($json, new JsonGetUserRequest());
            $uid = $request->uid;
            $pid = $request->pid;
            $sid = $request->sid;
            
            getuser($uid,$pid,$sid);
            } catch (Throwable $e) {
                send_response(-5, ERROR_BAD_DELETE_REQUEST);
            }
         break;

         case "createuser":
            try{
            $request = $mapper->map($json, new JsonCreateUserRequest());
            $uid = $request->uid;
            $pid = $request->pid;
            $login = $request->login;
            $password = $request->password;
            $role = $request->role;

            createuser($uid,$pid,$login,$password,$role);
            } catch (Throwable $e) {
            send_response(-5, ERROR_BAD_DELETE_REQUEST);
            };
         break;

         case "deleteuser":
            try {
            $request = $mapper->map($json, new JsonGetUserRequest());
            $uid = $request->uid;
            $pid = $request->pid;
            $sid = $request->sid;
            
            deleteuser($uid,$pid,$sid);
            } catch (Throwable $e) {
                send_response(-5, ERROR_BAD_DELETE_REQUEST);
            }
         break;
        
         case "changepassword":
            try {
            $request = $mapper->map($json, new JsonChangeUserRequest());
            $uid = $request->uid;
            $pid = $request->pid;
            $sid = $request->sid;
            $password = $request->password;

            changepassword($uid,$pid,$sid,$password);
            } catch (Throwable $e) {
                send_response(-5, ERROR_BAD_DELETE_REQUEST);
            }
         break;

        case "auth":
            try {
            $request = $mapper->map($json, new JsonInitRequest());
            $uid = $request->uid;
            $pid = $request->pid;
            auth($uid,$pid);
            } catch (Throwable $e) {
                echo($e);
                send_response(-5, ERROR_BAD_AUTH_REQUEST);
            }
            break;
        default:
            send_response(-3, ERROR_UNKNOWN_ACTION);
    }
}

action();

