<?php

// Enable all errors and set time limit to unlimited

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

// Get source folder

if (isset($_POST['source'])) $params['source'] = $_POST['source'];
else $params['source'] = '';

// Get match folder

if (isset($_POST['match'])) $params['match'] = $_POST['match'];
else $params['match'] = '';

// Get preview mode

if (isset($_POST['preview'])) $params['preview'] = $_POST['preview'];
else $params['preview'] = 'n';

// Get html form

$html = file_get_contents('finder.html');
foreach ($params as $key => $value) $html = str_replace('{$'.$key.'}', $value, $html);
echo $html;

// Process Request

if (!empty($params['source']) && !empty($params['match']))
{
    $sourcefiles = scandir($params['source']);
    $matchfiles = scandir($params['match']);

    $duplicates = Array();

    // Remove . and .. "files"

    foreach ($sourcefiles as $skey => $sourcefile) if ($sourcefile == '.' || $sourcefile == '..') unset($sourcefiles[$skey]);
    foreach ($matchfiles as $mkey => $matchfile) if ($matchfile == '.' || $matchfile == '..') unset($matchfiles[$mkey]);

    // Iterate through source files (files that will be deleted)

    foreach ($sourcefiles as $skey => $sourcefile)
    {
        // Check if there is a match in the match folder

        if (in_array($sourcefile, $matchfiles))
        {
            // Get the file size of the source file

            $sourcepath = "{$params['source']}/$sourcefile";
            $sourcesize = filesize($sourcepath);

            // Get the file size of the match file

            $matchpath = "{$params['match']}/$sourcefile";
            $matchsize = filesize($matchpath);

            // If the size of the source and match file are the same, delete the source file

            if ($sourcesize === $matchsize)
            {
                // Hash both files and make sure contents are the same before deleting the source file

                $sourcehash = hash_file('sha256', $sourcepath);
                $matchhash = hash_file('sha256', $matchpath);

                if ($sourcehash === $matchhash)
                {
                    $duplicates[] = "{$params['source']}/$sourcefile";
                    if ($params['preview'] !== 'y') unlink($sourcepath);
                }
            }
        }
    }

    $duplicatescount = count($duplicates);

    echo "<hr /><table cellspacing='10' cellpadding='50' width='100%'><tr><td><h3>Results:</h3>";

    debug($duplicatescount, 'Duplicate Files');
    debug($params, 'Params');
    debug($duplicates, 'Duplicates Removed');

    echo "</td></tr></table>";
}

if (!empty($_POST['source']) && empty($_POST['match']))
{
    $sourcefiles = scandir($params['source']);
    
    $filesize = Array();
    $hashes = Array();
    
    // Remove . and .. "files"

    foreach ($sourcefiles as $unsetkey => $unsetsourcefile)
    {
        if ($unsetsourcefile == '.' || $unsetsourcefile == '..') unset($sourcefiles[$unsetkey]);
        else if (is_dir("{$params['source']}/$unsetsourcefile")) unset($sourcefiles[$unsetkey]);
    }

    // Iterate through and get file sizes

    foreach ($sourcefiles as $skey => $sourcefile)
    {
        $sourcepath = "{$params['source']}/$sourcefile";
        $sourcesize = filesize($sourcepath);
        $filesize[$sourcesize][] = $sourcefile;
    }

    // Iterate through file sizes and remove entries with only one file

    foreach($filesize as $fkey => $filesizefile)
    {
        if (count($filesizefile) == 1) unset($filesize[$fkey]);
    }

    // Create hashes of each file to find duplicates

    foreach($filesize as $fkey => $filesizefiles)
    {
        foreach($filesizefiles as $filesizefile)
        {
            $filesizefilepath = "{$params['source']}/$filesizefile";
            $hash = hash_file('sha256', $filesizefilepath);
            $hashes[$hash][] = $filesizefile;
        }
    }

    // Iterate through hashes and remove entries with only one file

    $totalhashfiles = 0;
    foreach($hashes as $hash => $files)
    {
        if (count($files) == 1) unset($hashes[$hash]);
        else $totalhashfiles += count($files);
    }

    // Remove all duplicate hash entries

    $totalremovedfiles = 0;
    foreach($hashes as $hash => $files)
    {
        for($i = 1; $i < count($files); $i++)
        {
            $totalremovedfiles++;
            $sourcepath = "{$params['source']}/{$files[$i]}";
            if ($params['preview'] !== 'y') unlink($sourcepath);
        }
    }

    $hashcount = count($hashes);

    echo "<hr /><table cellspacing='10' cellpadding='50' width='100%'><tr><td><h3>Results:</h3>";

    debug($hashcount, "Hash Entries");
    debug($totalhashfiles, "Total Files");
    debug($totalremovedfiles, "Total Removed");
    debug($params, "Params");
    debug($hashes, "Duplicates Removed");

    echo "</td></tr></table>";
}

function debug($var, $title = '')
{
    if (!empty($title)) $title = "{$title}: ";

    echo "<pre>{$title}";
    print_r($var);
    echo "</pre>";
}
