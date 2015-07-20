<?php
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
require 'Slim/Slim.php';
require "NotORM.php";

$dsn = "mysql:dbname=fileListBase;host=localhost";
$username = "root";
$password = "1131494";

$pdo = new PDO($dsn, $username, $password);
$db = new NotORM($pdo);

\Slim\Slim::registerAutoloader();

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new \Slim\Slim();

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, `Slim::patch`, and `Slim::delete`
 * is an anonymous function.
 */

// GET route
$app->get(
    '/hello/:name',
    function ($name) use($app) {
	echo "Hello, $name";
    });

$app->get(
    '/',
    function () use($app, $db) {
	echo "Hello, jayashree";
    });

$app->get(
    '/file/:File_ID',
    function ($File_ID) use($app, $db) {
	
	$file = $db->file_list()->where("File_ID", $File_ID);
	$fileInfo = $file->fetch();
	if($fileInfo)
	{ 

		$fileload = './uploads/'.$fileInfo["File_Name"];
		$res = $app->response();

		$res['Content-Description'] = 'File Transfer';
		$res['Content-Type'] = $fileInfo["File_Type"];
		$res['Content-Disposition'] = 'attachment; filename='.$fileInfo["File_Name"];
		$res['Content-Transfer-Encoding'] = 'binary';
		$res['Content-Length'] = filesize($fileload);
		$res['Pragma'] = 'public';
	
		ob_clean();
		flush();	
	
		readfile($fileload);
		exit();
	}
	else
	{
		echo "Not found\n";

	}    	
});

$app->get(
	'/file_list',
	function() use($app, $db){
		$app->response()->header("Content-Type", "application/json");
		$fileList = array();
		foreach($db->file_list() as $file ) {
			$fileList[$file['File_ID']] = array(
				"File_ID"          => $file['File_ID'], 
	        		"File_Name"        => $file['File_Name'],
	        		"File_Server_Owner"  => $file['File_Server_Owner'],
	        		"File_Server_Name"      => $file['File_Server_Name'],
	        		"File_Type"        => $file['File_Type'],
	        		"File_Size"        => $file['File_Size'],
				"File_Location"    => $file['File_Location'],
				"File_Private"     => $file['File_Private'],
				"File_Server_Addr" => $file['File_Server_Addr']
		
			);
		}
	echo json_encode($fileList);
});

// POST route
$app->post(
    '/upload', 
	function() use($app, $db) {
	$dup = $db->file_list()->where("File_Name", $app->request->post('File_Name'));
	
	if($dup->fetch())
	{
		echo "duplicate";
	}	
	else
	{      
		$fileInfo = array(
	        	"File_ID"         => $app->request->post('File_ID'), 
	        	"File_Name"       => $app->request->post('File_Name'),
	        	"File_Server_Owner" => $app->request->post('File_Server_Owner'),
	        	"File_Server_Name"     => $app->request->post('File_Server_Name'),
	        	"File_Type"       => $app->request->post('File_Type'),
	        	"File_Size"       => $app->request->post('File_Size'),
			"File_Location"   => $app->request->post('File_Location'),
			"File_Private"    => $app->request->post('File_Private'),
			"File_Server_Addr" => $app->request->post('File_Server_Addr')
		);
		$db->file_list()->insert($fileInfo);
		echo "Inserted";
		
		uploadFile();
	}
});

// POST route
$app->post(
    '/delete', 
	function () use($app, $db) {
		$userId = $app->request->post('File_ID');
		$file = $db->file_list()->where("File_ID", $userId);
		$fileInfo = $file->fetch();
		if($fileInfo) {
			unlink('./uploads/' . $fileInfo["File_Name"]);
			$result = $file->delete();
			echo "File Deleted";
		}
		else
		{
			echo "file not found";
		}
	
});

$app->post(
	'/rename',
	   function () use($app, $db) {
		$userId  = $app->request->post('File_ID');
		$newName = $app->request->post('New_Name');
		$file = $db->file_list()->where('File_ID', $userId);
		$fileInfo = $file->fetch();
		if($fileInfo) {
			
			$ext = pathinfo($fileInfo["File_Name"], PATHINFO_EXTENSION);
			$newName = $newName.'.'.$ext;
			
			rename('./uploads/'.$fileInfo["File_Name"], './uploads/'.$newName);
			
			$fileInfo["File_Name"] = $newName;
			$file->delete();
			$db->file_list()->insert($fileInfo);
			
			echo "File Name Updated";
		}
		else
		{
			echo "File not found";
		}
});

// PUT route
$app->put(
    '/put',
    function () {
        echo 'This is a PUT route';
    }
);

// PATCH route
$app->patch('/patch', function () {
    echo 'This is a PATCH route';
});

// DELETE route
$app->delete(
    '/delete',
    function () {
        echo 'This is a DELETE route';
    }
);

function uploadFile() {
	
	$target_dir = "uploads/";
	$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
	$uploadOk = 1;
	$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
	// Check if image file is a actual image or fake image


	// Check if file already exists
	if (file_exists($target_file)) {
    		echo "Sorry, file already exists.";
    		$uploadOk = 0;
	}

	// Check file size
	if ($_FILES["fileToUpload"]["size"] > 12000000) {
    		echo "Sorry, your file is too large.";
    		$uploadOk = 0;
	}

	// Allow certain file formats

	// Check if $uploadOk is set to 0 by an error
	if ($uploadOk == 0) {
    		echo "Sorry, your file was not uploaded.";
		exit();
		// if everything is ok, try to upload file
	} else {
    		if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        	echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
    	} else {
        	echo "Sorry, there was an error uploading your file.";
    	}
	}	

}

/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
