<?php
    if(isset($_POST['action'])){
        // fileNames[sym] = {fileName: [filePath, tableNames]}
        // fileName = 'rawData' | 'charts' | 'analyzeChart'
        header("content-type:application/json");
        
        $action = $_POST['action'];
        $fileNames = $_POST['fileNames'];
        
        if($action == "fileNames"){
            $fileNameArray = [];
            
            foreach($fileNames as $sym => $fileNameObj){
                // fileNameObj = {varNames}
                foreach($fileNameObj as $name => $fileArray){
                    // fileNameObj[varName] = fileArray = [filePath, tableNames]
                    
                    foreach(glob($fileArray[0]."*.sqlite") as $fileNameWithExt){
                        if(strpos($fileNameWithExt, "test") !== false){
                            $fileName = basename($fileNameWithExt, ".sqlite");
                            
                            array_push($fileNameArray, $fileName);
                        }
                    }
                }
            }
            
            $fileNameArray = array_unique($fileNameArray);
            
            echo json_encode($fileNameArray);
        }else if($action == "tableNames"){
            // Get table names
            $tableArray = [];
            $tempTableNames = [];
            
            foreach($fileNames as $sym => $fileNameObj){
                // fileNameObj = {varNames}
                foreach($fileNameObj as $name => $fileArray){
                    // fileNameObj[varName] = fileArray = [filePath, tableNames]
                    $fileName = $fileArray[0];
                    $tableNames = $fileArray[1];
                    $tableArray[$name] = [];
                    
                    if($tableNames == null){
                        // No specific table names
                        $tempTableNames = [];
                        $db = new SQLite3($fileName);
                        $query = $db->query("SELECT name FROM sqlite_master where type = 'table'");
                    }else{
                        $whereClause = "(";
                        $whereArray = [];
                        foreach($tableNames as $tableName){
                            $whereStr = "name = '".$tableName."'";
                            array_push($whereArray, $whereStr);
                        }
                        
                        $whereClause .= implode(" OR ", $whereArray);
                        $whereClause .= ")";
                        
                        $db = new SQLite3($fileName);
                        $query = $db->query("SELECT name FROM sqlite_master where type = 'table' AND ".$whereClause);
                    }
                    
                    while($table = $query->fetchArray()){
                        array_push($tempTableNames, $table['name']);
                    }
                    
                    $tableArray[$name] = array_merge($tableArray[$name], $tempTableNames);
                }
            }
            
            foreach($tableArray as $name => $tables){
                $tableArray[$name] = array_unique($tables);
                sort($tables);
            }
            
            // $tableArray = [$fileName => $tableNames]
            echo json_encode($tableArray);
        }else if($action == "histData"){
            // Get historic data
            $tableNames = null;
            $histData = [];
            $tempHistData = [];
            $db = null; $stmt = ""; $result = null; $sym = "";
            
            foreach($fileNames as $sym => $fileNameObj){
                // fileNameObj = {varNames}
                foreach($fileNameObj as $name => $fileArray){
                    // fileNameObj[varName] = fileArray = [filePath, tableNames]
                    $tempHistData = [];
                    
                    $db = new SQLite3($fileArray[0]);
                    $tableNames = $fileArray[1];
                    for($i=0, $tableLen=count($tableNames); $i<$tableLen; $i++){
                        $tableName = $tableNames[$i];
                        $tableArray = [];
                        $query = $db->query("SELECT name FROM sqlite_master where type = 'table' AND name = '".$tableName."'");
                        while($table = $query->fetchArray()){
                            array_push($tableArray, $table['name']);
                        }
                        
                        if(count($tableArray) != 0){
                            $stmt = $db->prepare("SELECT * FROM '".$tableName."'");
                            $result = $stmt->execute();
                            
                            while($row = $result->fetchArray(SQLITE3_NUM)){
                                array_push($tempHistData, $row);
                            }
                            
                            if(!isset($histData[$sym])) $histData[$sym] = null;
                            if(!isset($histData[$sym][$name])) $histData[$sym][$name] = null;
                            $histData[$sym][$name][$tableNames[$i]] = $tempHistData;
                        }else{
                            if(!isset($histData[$sym])) $histData[$sym] = null;
                            if(!isset($histData[$sym][$name])) $histData[$sym][$name] = null;
                            $histData[$sym][$name][$tableNames[$i]] = null;
                        }
                    }
                }
            }
            
            echo json_encode($histData);
        }
    }else{
        echo "Hello~";
    }
?>