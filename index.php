<?php
include_once("omrprocessV1.cls.php");
$path = "images/image1.jpg";
$imageObject = new clsOmr();
$imageObject->createImage($path);
$imageObject->getDPI();
$imageObject->getTiltAngle();
$imageObject->setNoOfQuestions(42);
$imageObject->createTemplate();
$omrData = $imageObject->extractData();

echo "<pre>";
print_r($omrData);
echo "</pre>";

$path = "images/image2.jpg";
$imageObject = new clsOmr();
$imageObject->createImage($path);
$imageObject->getDPI();
$imageObject->getTiltAngle();
$imageObject->setNoOfQuestions(42);
$imageObject->createTemplate();
$omrData = $imageObject->extractData();

echo "<pre>";
print_r($omrData);
echo "</pre>";

$path = "images/image3.jpg";
$imageObject = new clsOmr();
$imageObject->createImage($path);
$imageObject->getDPI();
$imageObject->getTiltAngle();
$imageObject->setNoOfQuestions(42);
$imageObject->createTemplate();
$omrData = $imageObject->extractData();

echo "<pre>";
print_r($omrData);
echo "</pre>";
?>