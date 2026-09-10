<?php
// Obsolete handlers cannot bypass the new authorization and CSRF checks.
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){http_response_code(409);exit('Reload the Messages page before posting.');}
