<?php

/**
 * PHP CodeSniffer Tooling
 *
 * PHP version 8
 *
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 */

declare(strict_types=1);

//# https://github.com/squizlabs/PHP_CodeSniffer
$path = dirname(dirname(__FILE__));
$cspath = '/opt/application/vendor-for/rooms/bin/phpcs'; // #WAS /opt/phpcs/phpcs.phar
$standards = [
    'PSR12',
    'PEAR',
    'PSR1',
    'PSR2',
    'Squiz',
    'Zend',
];
$sniffs = [
    'Generic.ControlStructures.InlineControlStructure',
    'Generic.Files.ByteOrderMark',
    'Generic.Files.LineEndings',
    'Generic.Files.LineLength',
    'Generic.Formatting.DisallowMultipleStatements',
    'Generic.Functions.FunctionCallArgumentSpacing',
    'Generic.NamingConventions.UpperCaseConstantName',
    'Generic.PHP.DisallowAlternativePHPTags',
    'Generic.PHP.DisallowShortOpenTag',
    'Generic.PHP.LowerCaseConstant',
    'Generic.PHP.LowerCaseKeyword',
    'Generic.PHP.LowerCaseType',
    'Generic.WhiteSpace.DisallowTabIndent',
    'Generic.WhiteSpace.IncrementDecrementSpacing',
    'Generic.WhiteSpace.ScopeIndent',
    'PEAR.Functions.ValidDefaultValue',
    'PSR1.Classes.ClassDeclaration',
    'PSR1.Files.SideEffects',
    'PSR1.Methods.CamelCapsMethodName',
    'PSR12.Classes.AnonClassDeclaration',
    'PSR12.Classes.ClassInstantiation',
    'PSR12.Classes.ClosingBrace',
    'PSR12.Classes.OpeningBraceSpace',
    'PSR12.ControlStructures.BooleanOperatorPlacement',
    'PSR12.ControlStructures.ControlStructureSpacing',
    'PSR12.Files.DeclareStatement',
    'PSR12.Files.FileHeader',
    'PSR12.Files.ImportStatement',
    'PSR12.Files.OpenTag',
    'PSR12.Functions.NullableTypeDeclaration',
    'PSR12.Functions.ReturnTypeDeclaration',
    'PSR12.Keywords.ShortFormTypeKeywords',
    'PSR12.Namespaces.CompoundNamespaceDepth',
    'PSR12.Operators.OperatorSpacing',
    'PSR12.Properties.ConstantVisibility',
    'PSR12.Traits.UseDeclaration',
    'PSR2.Classes.ClassDeclaration',
    'PSR2.Classes.PropertyDeclaration',
    'PSR2.ControlStructures.ElseIfDeclaration',
    'PSR2.ControlStructures.SwitchDeclaration',
    'PSR2.Files.ClosingTag',
    'PSR2.Files.EndFileNewline',
    'PSR2.Methods.FunctionCallSignature',
    'PSR2.Methods.FunctionClosingBrace',
    'PSR2.Methods.MethodDeclaration',
    'Squiz.Classes.ValidClassName',
    'Squiz.ControlStructures.ControlSignature',
    'Squiz.ControlStructures.ForEachLoopDeclaration',
    'Squiz.ControlStructures.ForLoopDeclaration',
    'Squiz.ControlStructures.LowercaseDeclaration',
    'Squiz.Functions.FunctionDeclaration',
    'Squiz.Functions.FunctionDeclarationArgumentSpacing',
    'Squiz.Functions.LowercaseFunctionKeywords',
    'Squiz.Functions.MultiLineFunctionDeclaration',
    'Squiz.Scope.MethodScope',
    'Squiz.WhiteSpace.CastSpacing',
    'Squiz.WhiteSpace.ControlStructureSpacing',
    'Squiz.WhiteSpace.ScopeClosingBrace',
    'Squiz.WhiteSpace.ScopeKeywordSpacing',
    'Squiz.WhiteSpace.SuperfluousWhitespace',
];
$excluded = [
    'PEAR' => [
        'PEAR.Commenting.FileComment.MissingCategoryTag',
        'PEAR.Commenting.FileComment.MissingPackageTag',
        //'PEAR.Commenting.FileComment.MissingLinkTag', #optional
        'PEAR.Formatting.MultiLineAssignment.EqualSignLine',
        //'PEAR.Commenting.InlineComment.WrongStyle',

    ],
];
$lintStatusCodes = [2];
$ventExcluded = [
    'Generic.WhiteSpace.ScopeIndent',
];
$ventToken = '.vent.php';
$fileChars = '_A-Za-z0-9-'; // #NOTE do not include "."
$dirPrefix = 'local-dev\.';
$filePrefix = 'local-dev\.|example\.|php[\d]+\.|'; // #NOTE e.g. do not include env.
$fileSuffix = '\.tether|\.root|\.vent|\.pylon|\.bulb|\.quay|\.sema';
$valid = "/^(({$dirPrefix})?[{$fileChars}]+\/)*($filePrefix)*[{$fileChars}]+({$fileSuffix})?\.php$/";
$allowedRoots = [
    '/opt/application/rooms',
];
$standard =
    key_exists('standard', $_GET)
        && in_array($_GET['standard'], $standards)
    ? $_GET['standard']
    : $standards[0];
$file = key_exists('scan', $_GET) ? $_GET['scan'] : false;
if ($file) {
    $allowedExternal = false;
    foreach($allowedRoots as $rootPath) {
        $validStart = substr($valid, 0, 2);
        $validRest = substr($valid, 2);
        if (strpos($file,"{$rootPath}/") === 0) {
            $testFile = substr($file, strlen("{$rootPath}/"));
            $escapedRootPath = addcslashes($rootPath, '/');
            if ( preg_match($valid, $testFile) === 1 ) {
                $allowedExternal = true;
                $path = $rootPath;
                $file = $testFile;
                break;
            }
        }
    }
    $allowedExternal || preg_match($valid, $file) === 1 || throw new Exception('Invalid File Selection');
    $format = '--report=json';
    $fullpath = "{$path}/{$file}";
    $validFile = is_file($fullpath) && is_readable($fullpath);
    $exclude = [];
    if (strrpos($file, $ventToken) === strlen($file) - strlen($ventToken)) {
        $exclude = array_unique(array_merge($exclude, $ventExcluded));
    }
    $excludeOption = count($exclude) ? (' --exclude=' . implode(',', $exclude)) : '';
    //--generator=HTML|Markdown|Text
    $set = "--standard={$standard}{$excludeOption}";
    $filter = "--basepath={$path}";
    $params = "-s {$format} {$set} {$filter} {$fullpath}";
} elseif (key_exists('list', $_GET)) {
    $params = key_exists('sniffs', $_GET) ? '-i' : "-e --standard={$standard}";
} elseif (key_exists('valid', $_GET)) { 
    print("valid files match: {$valid}");
    return;
} else {
    print("specify ?scan=&lt;file&gt; or ?list");
    return;
}


if (!$validFile) {
    print("invalid file specified: {$file}");
    return;
}

$buffer = [];
$status = 0;
exec("php {$cspath} {$params}", $buffer, $status);
if ($status && !in_array($status, $lintStatusCodes)) {
    array_unshift($buffer, "phpcs returned status {$status}");
    $buffer[] = "command was: <span>phpcs {$params}</span>";
}
print(implode("<br>\n", $buffer));
