<?php
/* *************************************************************************** 
* Name: IPP Project 1. part 
* Author: Simona Ceskova xcesko00
* Date: 2.3.2022
*************************************************************************** */
// 41 adresar neexistuje nebo neni dostupny

$outputList = [];
$testList = [];

function help()
{
    echo "Help:\n";
    echo "Usage 1: php8.1 test.php [parameters]";
    echo "\n__________________________________\n";
    echo "If path is not included it takes actual folder\n";
    echo "--directory=\"path\"\n";
    echo "--parse-script=\"file\"\n";
    echo "--int-script=\"file\"\n";
    echo "__________________________________\n";
    echo "--int-only (cant combine with --parse-only/script and --jaxampath)\n";
    echo "--parse-only (cant combine with --int-only/script)\n";
    echo "--jaxampath=\"path\" (cant combine with --int-only)";
    echo "\n__________________________________\n";
    echo "--noclean\n";
    echo "--recursive\n";
    exit(0);
}
/****************** PARSE **************/
function outputCheckParser($jexamPath, $parsePath, $jexamOption, $folder, $clean){
    global $testList;
    global $outputList;
    // prechodny soubor s vygenerovanym xml kodem
    $genXML = $folder.".gen";
    // spusteni parse.php programu
    $folder2 = $folder = str_replace(".src", "", $folder);
    //'php8.1 ' . $parsePath . ' < ' . $folder2 . '.src > ' . $folder2.'.src.gen' . ' 2>' . $folder2.'.err';
    $cmdParser = 'php8.1 ' . $parsePath . ' < ' . $folder2 . '.src > ' . $folder2.'.src.gen' . ' 2>' . $folder2.'.err';
    // errorCode pri generovani XML
    exec($cmdParser, $output, $errorXML);

    // vytvoreni souboru, pokud nejsou vytvoreny
    $rc = $folder.'.rc';
    if(!file_exists($rc))
        file_put_contents($rc, '0');
    $out = $folder.'.out';
    if(!file_exists($out))
        file_put_contents($out, '');

    $errorRC = (int)file_get_contents($rc);

    // porovnavani jestli test prosel, nebo ne
    array_push($testList, $folder);
    if($errorRC != $errorXML){
        array_push($outputList, "FAIL");
    }
    else if($errorRC != 0)
    {
        array_push($outputList, "OK");
    }
    else{
        //kontrola XML
        $folder = str_replace(".src", "", $folder);
        $cmd = 'java -jar ' . $jexamPath .' ' . $folder.'.out' . ' ' . $folder.'.src.gen' . ' ' . $folder.'.diff' . ' ' . $jexamOption;
        exec($cmd, $output, $errorCode); //errorCode co generuje parser
        if($errorCode == 0)
            array_push($outputList, "OK");
        else
            array_push($outputList, "FAIL");
    }
    if($clean)
        unlink($genXML);
}

function recursionParse($jexamPath, $parsePath, $jexamOption, $directory, $clean){
    //prochazeni slozek a hledani .src souboru
    $scanDir = scandir($directory);
    foreach ($scanDir as $folder){
        if(($folder == '..') || ($folder == '.'))
        {
            continue;
        }
        $realPath = realpath($directory."/".$folder);
        if(is_dir($realPath))
        {
            recursionParse($jexamPath, $parsePath, $jexamOption, $realPath, $clean);
        }else{
            //jaxem generuje .log soubory
            if(str_contains($folder, '.log')){
                unlink($realPath);
                continue;
            }
            if(str_contains($folder, '.src') and !str_contains($folder, '.gen'))
                outputCheckParser($jexamPath, $parsePath, $jexamOption, $realPath, $clean);   
        }
    }
}

function parse($directory, $parseScript, $jexam, $clean, $recursion){
    //nastaveni spravnych cest
    $jexamPath = $jexam."/jexamxml.jar";
    $jexamOption = $jexam."/options";
    $parsePath = $parseScript."/parse.php";
    if(!file_exists($jexamPath) or !file_exists($jexamOption))
        exit(41);
    //pokud neni zadana rekurze, tak prejde do else, kde vypada kontrola stejne jako jedna rekurze
    if($recursion)
        recursionParse($jexamPath, $parsePath, $jexamOption, $directory, $clean);
    else
    {
        $scanDir = scandir($directory);
        foreach ($scanDir as $folder){
            if(($folder == '..') || ($folder == '.'))
            {
                continue;
            }
            $realPath = realpath($directory."/".$folder);
            if(is_dir($realPath))
            {
                continue;
            }else{
                //pokud se najde test s priponou .src
                if(str_contains($folder, '.src'))
                    outputCheckParser($jexamPath, $parsePath, $jexamOption, $realPath, $clean);   
            }
        } 
    }
}
/****************** PARSE **************/
/****************** INTERPRET **************/
function outputCheckInt($intPath, $folder, $clean){
    global $testList;
    global $outputList;
    //vytvareni nevytvorenych souboru
    $rc = str_replace(".src", ".rc", $folder);
    if(!file_exists($rc))
        file_put_contents($rc, '0');
    $out = str_replace(".src", ".out", $folder);
    if(!file_exists($out))
        file_put_contents($out, '');
    $in = str_replace(".src", ".in", $folder);
    if(!file_exists($in))
        file_put_contents($in, '');

    //pomocne soubory
    $temp1 = str_replace(".src", ".tmp.gen", $folder);
    $temp2 = str_replace(".src", ".diff.gen", $folder);
    file_put_contents($temp1, '');
    file_put_contents($temp2, '');

    //spusteni programu interpret.py
    $cmdInt = "python3.8 $intPath --source=$folder --input=$in > $temp1";
    exec($cmdInt, $output, $error);
    $errorRC = (int)file_get_contents($rc);
    
    array_push($testList, $folder);
    if($error != $errorRC)
        array_push($outputList, "FAIL");
    else if($errorRC != 0)
        array_push($outputList, "OK");
    else{
        // porovnavani testu
        $cmd = "diff $temp1 $out > $temp2";
        //errorCode co generuje interpret
        exec($cmd, $output, $errorCode); 
        if($errorCode == 0)
            array_push($outputList, "OK");
        else
            array_push($outputList, "FAIL");
    }

    if($clean){
        unlink($temp1);
        unlink($temp2);
    }
}
function recursionInt($intPath, $directory, $clean){
    //prochazeni slozek a hledani .src souboru
    $scanDir = scandir($directory);
    foreach ($scanDir as $folder){
        if(($folder == '..') || ($folder == '.'))
            continue;
        $realPath = realpath($directory."/".$folder);
        if(is_dir($realPath))
        {
            recursionInt($intPath, $realPath, $clean);
        }else{
            if(str_contains($folder, '.log')){
                unlink($folder);
                continue;
            }
            if(str_contains($folder, '.src'))
                outputCheckInt($intPath, $realPath, $clean);   
        }
    }
}
function interpret($directory, $intScript, $clean, $recursion){
    $intPath = $intScript."/interpret.py";
    //pokud neni zadana rekurze, tak prejde do else, kde vypada kontrola stejne jako jedna rekurze
    if($recursion)
        recursionInt($intPath, $directory, $clean);
    else{
        $scanDir = scandir($directory);
        foreach ($scanDir as $folder){
            if(($folder == '..') || ($folder == '.'))
            {
                continue;
            }
            $realPath = realpath($directory."/".$folder);
            if(is_dir($realPath))
            {
                continue;
            }else{
                //pokud se najde test s priponou .src
                if(str_contains($folder, '.src'))
                    outputCheckInt($intPath, $realPath, $clean);   
            }
        } 
    }
}
/****************** INTERPRET **************/
/****************** BOTH **************/
function outputCheckBoth($parsePath, $intPath, $folder, $clean){
    global $testList;
    global $outputList;
    //spusteni parse.php na vygenerovani XML s kterym bude dal pracovat interpret
    $genXML = str_replace(".src", ".gen", $folder);
    $cmdParser = "php8.1 $parsePath <$folder >$genXML";

    //vytvareni nevytvorenych souboru
    $rc = str_replace(".src", ".rc", $folder);
    if(!file_exists($rc))
        file_put_contents($rc, '0');
    $out = str_replace(".src", ".out", $folder);
    if(!file_exists($out))
        file_put_contents($out, '');
    $in = str_replace(".src", ".in", $folder);
    if(!file_exists($in))
        file_put_contents($in, '');

    //spusteni interpret.py
    $cmdInterpret = "python3.8 $intPath --source=$genXML --input=$in >$out.tmp";
    exec($cmdParser, $output, $errorXML); //errorCode pri generovani XML

    //pomocny soubor
    $temp = str_replace(".src", ".diff.gen", $folder);
    file_put_contents($temp, '');

    $errorRC = (int)file_get_contents($rc);
    array_push($testList, $folder);
    if ($errorXML != 0)
        array_push($outputList, "FAIL");
    else{
        //errorCode co generuje interpret
        exec($cmdInterpret, $output, $errorCode); 
        if($errorCode != $errorRC)
            array_push($outputList, "FAIL");
        elseif($errorRC != 0)
            array_push($outputList, "OK");
        else{ //kontrola XML
            $cmd = "diff $out $out.tmp >$temp";
            //errorCode co generuje interpret
            exec($cmd, $output, $errorCode);
            if($errorCode == 0)
                array_push($outputList, "OK");
            else
                array_push($outputList, "FAIL");
        }
    }
    //mazani generovanuch souboru
    if($clean){
        unlink($genXML);
        unlink($temp);
        unlink($out.".tmp");
    }
}
function recursionBoth($parsePath, $intPath, $directory, $clean){
    //prochazeni slozek a hledani .src souboru
    $scanDir = scandir($directory);
    foreach ($scanDir as $folder){
        if(($folder == '..') || ($folder == '.'))
            continue;
        $realPath = realpath($directory."/".$folder);
        if(is_dir($realPath))
        {
            recursionBoth($parsePath, $intPath, $realPath, $clean);
        }else{
            if(str_contains($folder, '.log')){
                unlink($folder);
                continue;
            }
            if(str_contains($folder, '.src'))
                outputCheckBoth($parsePath, $intPath, $realPath, $clean);   
        }
    }  
}
function both($directory, $parseScript, $intScript, $clean, $recursion){
    //prirazeni spravnych cest
    $parsePath = $parseScript."/parse.php";
    $intPath = $intScript."/interpret.py";
    if($recursion)
        recursionBoth($parsePath, $intPath, $directory, $clean);
    else{
        $scanDir = scandir($directory);
        foreach ($scanDir as $folder){
            if(($folder == '..') || ($folder == '.'))
            {
                continue;
            }
            $realPath = realpath($directory."/".$folder);
            if(is_dir($realPath))
            {
                continue;
            }else{
                // pokud dojde na file.src, tak prejde do testovani
                if(str_contains($folder, '.src'))
                    outputCheckBoth($parsePath, $intPath, $realPath, $clean);   
            }
        } 
    }
}
/****************** BOTH **************/
function checkArgs($argv, $argc)
{
    //zapsani vsech argumentu do pole
    $list = [];
    for ($i = 0; $i < $argc; $i++) {
        $arg = $argv[$i];
        // separovani od path, pro lepsi praci
        if( str_contains($argv[$i], '='))
        {
            $args = explode('=', $arg, 2);
            array_push($list, $args[0]);
            array_push($list, $args[1]);
        }
        else
            array_push($list, $arg);
    }
    //podminky platneho zadani argumentu
    if ( in_array('--parse-only', $list, true) && (in_array('--int-only', $list, true) || in_array('--int-script', $list, true)) )
        exit(10);
    if ( in_array('--int-only', $list, true) && (in_array('--parse-only', $list, true) || in_array('--parse-script', $list, true) || in_array('--jexampath', $list, true)) )
        exit(10);

    //defaultni cesty
    $recursion = false;
    $clean = true;
    $jexam = realpath('/pub/courses/ipp/jexamxml/');
    $directory = getcwd();
    $parseScript = getcwd();
    $intScript = getcwd();

    //upresnovani cest k testum a parse.php, interpret.py
    for ($i = 1; $i < count($list); $i++) {
        switch ($list[$i]) {
            case "--help": {
                    if ($argc == 2) {
                        help();
                    } else {
                        exit(10);
                    }
                }
            case "--directory": { 
                $directory = $list[$i+1];
                $i++;
                break;
            }
            case "--parse-script": {
                $parseScript = $list[$i+1];
                // /parse.php ma 10 znaku
                $parseScript = realpath(substr($parseScript, 0, -10));
                $i++;
                break;
            }
            case "--int-script": {
                // /interpret.py ma 10 znaku
                $intScript = $list[$i+1];
                $intScript = realpath(substr($intScript, 0, -13));
                $i++;
                break;
            }
            case "--jexampath": { 
                $jexam = $list[$i+1];
                $i++;    
                break;
            }
        }
    }
    //bool hodnoty pro akce
    if ( in_array('--noclean', $list, false)){
        $clean = false;
    }
    if ( in_array('--recursive', $list, false)){
        $recursion = true;
    }
    //kontrola jestli cesty existuji
    /* if (!is_dir($directory) or !is_dir($parseScript) or !is_dir($intScript) or !is_dir($jexam))
        exit(41); */
    //vyber jake testy se spusti podle zadanych parametru
    if ( in_array('--parse-only', $list, true)){
        parse($directory, $parseScript, $jexam, $clean, $recursion);
    }
    else if( in_array('--int-only', $list, true)){
        interpret($directory, $intScript, $clean, $recursion);
    }
    else
        both($directory, $parseScript, $intScript, $clean, $recursion);
}

function table(){
    global $testList;
    global $outputList;
    for($i = 0; $i < count($testList); $i++){
        $toPrint = "<td id=\"FAIL\">FAIL</td>";
        if($outputList[$i] == "OK")
            $toPrint = "<td id=\"OK\">OK</td>";
        echo "<tr>
            <td>".$testList[$i]."</td>
            <td>".$toPrint."</td>
        </tr>";
    }
}

function statistic(){
    global $outputList;
    $counter = 0;
    foreach ($outputList as $each){
        if($each == "OK")
        {
            $counter++;
        }
    }
    echo "<h4>Proslo ".$counter."/".count($outputList)."testu.</h4>\n";
    if(count($outputList) == 0)
        echo "<h4>Statistika uspesnosti: 0% uspesnych.</h4>\n";
    else
    {
        $procento = $counter/count($outputList)*100;
        echo "<h4>Statistika uspesnosti:".$procento."% uspesnych.</h4>\n";
    }    
}

function html(){
    echo"<!doctype html>
    <html lang=\"en\">
    <head>
    <style type=\"text/css\">
        h1, h3{
            text-align: center;
        }
        table td#OK {
            color: green;
        }
        table td#FAIL {
            color: red;
        }
    </style>
    <meta charset=\"UTF-8\">
    <title>IPP</title>
    </head>
    <body>
        <h1>IPP testy</h1>";
        statistic();
    echo "<table>";
            table();
    echo "</table>
    </body>
    </html>";
}

function main($argv, $argc){
    checkArgs($argv, $argc);
    html();
}

main($argv, $argc);
?>
