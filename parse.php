<?php
/* ************************************************************************************** 
* Name: IPP Project 1. part 
* Author: Simona Ceskova xcesko00
* Date: 16.03.2022
***************************************************************************************** */
/* ************************************ARG & HELP *************************************** */

function checkArgs($argv, $argc)
{
    for ($i = 0; $i < $argc; $i++) {
        if ($argv[$i] == "--help") {
            if ($argc == 2) {
                help();
            } else {
                exit(10);
            }
        }
    }
    if ($argc != 1) {
        exit(10);
    }
}

function help()
{
    echo "Help:\n";
    echo "Usege 1: php8.1 parse.php <input\n";
    echo "Usege 2: php8.1 parse.php --help";
    echo "\n---------------------------------\n";
    echo "comments: #\n";
    echo "head: .IPPcode22\n";
    echo "body: INSTRUCTION <var>/<lab>/<type> ...\n";
    exit(0);
}
/* ************************************ARG & HELP ****************************************** */
/* ************************************WRITER FUNKCE *************************************** */
function printStartInst($order, $opcode, $writer) //<instruction order="$order" opcode="$opcode">
{
    $writer->startElement('instruction');
    $writer->startAttribute('order');
    $writer->text($order);
    $writer->endAttribute();
    $writer->startAttribute('opcode');
    $writer->text(strtoupper($opcode));
    $writer->endAttribute();
}

function printStartArg($number, $type, $name, $writer) //<arg$number type="$type">$name</arg1>
{ //podle $number se vybere poradi argumentu
    switch ($number) {
        case 1: {
                $writer->startElement('arg1');
                break;
            }
        case 2: {
                $writer->startElement('arg2');
                break;
            }
        case 3: {
                $writer->startElement('arg3');
                break;
            }
        default:
            exit(23);
    }
    $writer->startAttribute('type');
    $writer->text($type);
    $writer->endAttribute();
    $writer->text($name);
    $writer->endElement();
}
/* ************************************WRITER FUNKCE *************************************** */
/* ********************************* REGEX A KONTROLA ************************************** */
function regexCheck($x, $switcher) // $x je string/array ktery je treba zkontrolovat
{                                  // $switcher: 1 pro navesti a promenne, 0 pro konstanty
    $str = "";
    if (gettype($x) == "array") { //pro pripad kdy prijde misto stringu array
        $str = implode($x);
        if ($switcher) {
            if ((preg_match('/^[0-9\s\,\#\\\\]+/', $str)) || (preg_match('/[\s\,\#\\\\]+/', $str))) // var lab
                exit(23);
        } else {
            if (!preg_match('/^([ěščřžýáíéůúa-zA-Z0-9_\-\$&%\*\!\?\/\<\>\&]|(\\\\\d{3}))+$/', $str)) { //symb
                if ($str != null)
                    exit(23);
            }
        }
    } else { //pro string
        if ($switcher) {
            if ((preg_match('/^[0-9\s\,\#\\\\]+/', $x)) || (preg_match('/[\s\,\#\\\\]+/', $x))) // var lab
                exit(23);
        } else {
            if (!preg_match('/^([ěščřžýáíéůúa-zA-Z0-9_\-\$&%\*\!\?\/\<\>\&]|(\\\\\d{3}))+$/', $x)) { //symb
                if ($str != null)
                    exit(23);
            }
        }
    }
}

function atsignCheck($x) //kontrola jestli obsahuje @ prave jednou,
{                        // $x je string ke kontrole
    $x_split = str_split($x);
    $n = 0;
    for ($i = 0; $i < count($x_split); $i++) {
        if ($x_split[$i] == '@') {
            $n++;
            if ($n == 2) {
                exit(23);
            }
        }
    }
}

function labCheck($x, $number, $writer) //
{ // $x je string ke kontrole, $number je poradi argumentu k vypsani
    regexCheck($x, 1);
    $x_split = str_split($x);

    if (is_numeric($x_split)) {
        exit(23);
    }
    for ($i = 0; $i < count($x_split); $i++) { //navesti nema @, proto ma vlastni kontrolu
        if ($x_split[$i] == '@') {
            exit(23);
        }
    }
    printStartArg($number, "label", $x, $writer);
}

function varCheck($x, $number, $writer) //kontrola promennych
{ // $x je string ke kontrole, $number je poradi argumentu k vypsani
    atsignCheck($x);
    $n = 0;
    $word = explode("@", $x);
    $first = $word[0];
    $second = $word[1];
    $arr = array("GF", "LF", "TF");

    $n = 0;
    foreach ($arr as $part) {    //pokud obsahuje prave jednou GF/LF/TF
        if (!strcmp($first, $part))
            $n++;
    }
    if ($n != 1)
        exit(23);

    $first .= "@";
    $first .= $second; //first je je string typu napr GF@temp

    regexCheck($second, 1);
    printStartArg($number, "var", $first, $writer);
}

function typesCheck($x, $number, $writer) //kontrola typu
{ // $x je string ke kontrole, $number je poradi argumentu k vypsani
    $arr = array("bool", "int", "string");
    if (!in_array($x, $arr)) {
        exit(23);
    }
    printStartArg($number, "type", $x, $writer);
}
function symbCheck($x, $number, $writer) //kontrola konstant
{ // $x je string ke kontrole, $number je poradi argumentu k vypsani
    atsignCheck($x);

    $word = explode("@", $x);
    $first = $word[0];
    $second = $word[1];
    $letters = str_split($second);

    switch ($first) {
        case "bool": { //bool muze byt jen true/false
                if (($second != "true") && ($second != "false"))
                    exit(23);
                break;
            }
        case "string": {
                regexCheck($letters, 0);
                break;
            }
        case "int": {
                foreach ($letters as $letr) { //musi zacinat pismenem, kladnym nebo zapornym
                    if (!is_numeric($letr) && $letters[0] != '-' && $letters[0] != '+')
                        exit(23);
                }
                break;
            }
        case "nil": {
                if (($second != NULL) && $second != "nil")
                    exit(23);
                break;
            }
        default: { //v pripade, ze symb neni konstanta, ale promenna
                varCheck($x, $number, $writer);
                return;
            }
    }
    printStartArg($number, $first, $second, $writer);
}
/* *********************************** REGEX A KONTROLA ******************************************* */
/* ******************************** KOMENTARE a BILE ZNAKY **************************************** */
function removeWhite($line) //vsechny prebytecne bile znaky se odstrani
{                           // $line je jeden radek
    $line = trim(preg_replace('/\s*/', " ", $line));
    $arr = explode("  ", $line);
    for ($i = 0; $i < count($arr); $i++) {
        $arr[$i] = preg_replace('/\s*/', "", $arr[$i]);
    }
    return $arr;
}
function commentCheckHead($arr, $n) //kontrola prazdnych radku s komentarem, dokud jeste nebyla nactena hlavicka
{                                   // $arr je pole stringu, $n udava index ke kontrole
    if (count($arr) != $n) {
        $letters = str_split($arr[$n]);
        if ($letters[0] != '#') {
            exit(23);
        }
    }
    return 1;
}
function commentCheck($arr, $n)    //kontrola prazdnych radku s komentarem, dokud jeste nebyla nactena hlavicka
{                                  // $arr je pole stringu, $n udava index ke kontrole
    //index = pocet argumentu + 1, ktera dana intrukce pozaduje -> jakykoliv slovo za poslednim argumentem musi byt komentar
    if (count($arr) < $n)
        exit(23);
    else if (count($arr) != $n) {
        $letters = str_split($arr[$n]);
        if ($letters[0] != '#')
            exit(23);
    }
}
/* ******************************** KOMENTARE a BILE ZNAKY **************************************** */
/* ************************************ PARSE START *********************************************** */
function stdinRead($writer)
{
    //nacteni ze stdin
    $file = fopen('php://stdin', 'r');
    if (!$file) {
        exit(11);
    }

    $line = fgets($file);
    while (!strcmp($line[0], "#")) {
        $line = fgets($file);
    }

    $arr = removeWhite($line);

    while ($arr[0] == null) {
        $line = fgets($file);
        $arr = removeWhite($line);
    }
    if (preg_match("'\#'", $arr[0])) {
        $expl = explode("#", $arr[0]);
        return $expl[0];
    }
    if (strcasecmp($arr[0], ".IPPcode22")) {
        exit(21);
    }
    $order = 0;

    while ($line = fgets($file)) {
        $order++;
        $arr = removeWhite($line);
        //rozdeleni instrukci podle skupin argumentu
        /* *** VAR SYMB *** */ if (!strcasecmp($arr[0], "INT2CHAR") || !strcasecmp($arr[0], "MOVE") || !strcasecmp($arr[0], "NOT") || !strcasecmp($arr[0], "STRLEN") || !strcasecmp($arr[0], "TYPE")) {
            commentCheck($arr, 3);
            printStartInst($order, $arr[0], $writer);
            varCheck($arr[1], 1, $writer);
            symbCheck($arr[2], 2, $writer);
            $writer->endElement();
        }
        /* *** - *** */ else if (!strcasecmp($arr[0], "CREATEFRAME") || !strcasecmp($arr[0], "PUSHFRAME") || !strcasecmp($arr[0], "POPFRAME") || !strcasecmp($arr[0], "RETURN") || !strcasecmp($arr[0], "BREAK")) {
            commentCheck($arr, 1);
            printStartInst($order, $arr[0], $writer);
            $writer->endElement();
        }
        /* *** SYMB *** */ else if (!strcasecmp($arr[0], "EXIT") || !strcasecmp($arr[0], "DPRINT") || !strcasecmp($arr[0], "PUSHS") || !strcasecmp($arr[0], "WRITE")) {
            commentCheck($arr, 2);
            printStartInst($order, $arr[0], $writer);
            for ($j = 1; $j < 2; $j++) {
                symbCheck($arr[$j], $j, $writer);
            }
            $writer->endElement();
        }
        /* *** LABEL *** */ else if (!strcasecmp($arr[0], "JUMP") || !strcasecmp($arr[0], "LABEL") || !strcasecmp($arr[0], "CALL")) { //1
            commentCheck($arr, 2);
            printStartInst($order, $arr[0], $writer);
            labCheck($arr[1], 1, $writer);

            $writer->endElement();
        }
        /* *** VAR SYMB SYMB *** */ else if (!strcasecmp($arr[0], "STRI2INT") || !strcasecmp($arr[0], "CONCAT") || !strcasecmp($arr[0], "GETCHAR") || !strcasecmp($arr[0], "SETCHAR") || !strcasecmp($arr[0], "ADD") || !strcasecmp($arr[0], "MUL") || !strcasecmp($arr[0], "IDIV") || !strcasecmp($arr[0], "SUB") || !strcasecmp($arr[0], "AND") || !strcasecmp($arr[0], "OR") || !strcasecmp($arr[0], "LT") || !strcasecmp($arr[0], "GT") || !strcasecmp($arr[0], "EQ")) {
            commentCheck($arr, 4);
            printStartInst($order, $arr[0], $writer);
            varCheck($arr[1], 1, $writer);
            for ($j = 2; $j < 4; $j++) {
                symbCheck($arr[$j], $j, $writer);
            }
            $writer->endElement();
        }
        /* *** LABEL SYMB SYMB *** */ else if (!strcasecmp($arr[0], "JUMPIFNEQ") || !strcasecmp($arr[0], "JUMPIFEQ")) {
            commentCheck($arr, 4);
            printStartInst($order, $arr[0], $writer);
            labCheck($arr[1], 1, $writer);
            for ($j = 2; $j < 4; $j++) {
                symbCheck($arr[$j], $j, $writer);
            }
            $writer->endElement();
        }
        /* *** VAR *** */ else if (!strcasecmp($arr[0], "POPS") || !strcasecmp($arr[0], "DEFVAR")) {
            commentCheck($arr, 2);
            printStartInst($order, $arr[0], $writer);
            varCheck($arr[1], 1, $writer);
            $writer->endElement();
        }
        /* *** VAR TYPE *** */ else if (!strcasecmp($arr[0], "READ")) {
            commentCheck($arr, 3);
            printStartInst($order, $arr[0], $writer);
            varCheck($arr[1], 1, $writer);
            typesCheck($arr[2], 2, $writer);
            $writer->endElement();
        }
        /* *** ELSE *** */ else {
            $smth = str_split($arr[0]);
            if ($smth[0] != "#" && $smth[0] != NULL)
                exit(22);
            $order--;
        }
    }
    fclose($file);
}
/* ********************************* PARSE KONEC ***************************************** */
/* ********************************** MAIN START ***************************************** */
checkArgs($argv, $argc);

$writer = new XMLWriter();
$writer->openURI('php://output');
$writer->startDocument('1.0', 'UTF-8');
$writer->setIndent(true);
$writer->startElement('program');
$writer->startAttribute('language');
$writer->text('IPPcode22');

stdinRead($writer);

$writer->endDocument();
exit(0);
/* *********************************** MAIN KONEC **************************************** */
?>
