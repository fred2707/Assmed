<?php
session_start();
try {
    $bd= new PDO("mysql:host=localhost;dbname=assmed","root","");
} catch(Exception $e) 
{
    die("Erreur de connexion a la BD");
}
?> 