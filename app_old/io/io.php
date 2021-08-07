
/**
 * Createa new directory recursively 
 *
 * Creates a directory recursively.  Goes through the parent directories, and 
 * creates them as necessary if they do not exist. 
 *
 * @param string $dirname The directory to create.
 */
public function create_dir(string $dirname)
{ 

    // Debug
    debug::add(4, tr("Creating new directory at {1}", $dirname));

    if (is_dir($dirname)) { return; }
    $tmp = str_replace("/", "\\/", sys_get_temp_dir());

    // Format dirname
    if (!preg_match("/^$tmp/", $dirname)) { 
        $dirname = trim(str_replace(SITE_PATH, "", $dirname), '/');
        $site_path = SITE_PATH;
    } else { 
        $site_path = sys_get_temp_dir();
        $dirname = preg_replace("/^$tmp/", "", $dirname);
    }
    $dirs = explode("/", $dirname);

    // Go through dirs
    $tmp_dir = '';
    foreach ($dirs as $dir) { 
        if ($dir == '') { continue; }
        $tmp_dir .= '/' . $dir;
        if (is_dir($site_path . '/' . $tmp_dir)) { continue; }

        // Create directory
        if (!mkdir($site_path . '/' . $tmp_dir)) {  
            throw new IOException('no_mkdir', $tmp_dir);
        }
    }

    // Return
    return true;

}

/**
 * Create a blank directory.
 * 
 * Creates a new directory recursively, and will also first remove 
 * the directory if it already exists, ensuring the newly 
 * created directory is blank.
 *
 * @param string $dir_name The name of the directory to create.
 */
public function create_blank_dir(string $dir_name)
{

    // Remove, if exists
    if (is_dir($dir_name)) { 
        $this->remove_dir($dir_name);
    }

    // Create directory
    $this->create_dir($dir_name);

}

/**
 * Remove a directory 
 *
 * Removes a directory recursively.  Goes through all files and 
 * sub-directories, and deletes them before deleting the parent directory. 
 *
 * @param string $dirname The directory name to delete.
 */

 */
public function create_zip_archive(string $tmp_dir, string $archive_file)
{ 

    // Debug
    debug::add(2, tr("Creating a new zip archive from directory {1} and aving at {2}", $tmp_dir, $archive_file));
 
    if (file_exists($archive_file)) { @unlink($archive_file); }
    $zip = new ZipArchive();
    $zip->open($archive_file, ZIPARCHIVE::CREATE);

    // Go through files
    $files = self::parse_dir($tmp_dir, true);
    foreach ($files as $file) { 
        if (is_dir($tmp_dir . '/' . $file)) { 
            $zip->addEmptyDir($file);
        } else { 
            $zip->addFile($tmp_dir . '/' . $file, $file);
        }
    }
    $zip->close();

    // Return
    return true;

}

/**
 * Unpack a zip archive 
 *
 * @param string $zip_file The path to the .zip archive
 * @param string $dirname The directory to create and unpack the archive to
 *
 * @return bool Whether or not the operation was successful.
 */
public function unpack_zip_archive(string $zip_file, string $dirname)
{ 

    // Debug
    debug::add(2, tr("Unpacking zip archive {1} into the directory {2}", $zip_file, $dirname));

    // Ensure archive file exists
    if (!file_exists($zip_file)) { 
        throw new IOException('zip_not_exists', $zip_file);
    }

    // Create directory to unpack to
    $this->create_blank_dir($dirname);

    // Open zip file
    $zip = new ZipArchive;
    if (!$zip->open($zip_file)) { 
        throw new IOException('zip_invalid', $zip_file);
    }

    // Extract zip file
    if (!$zip->extractTo($dirname)) { 
        throw new IOException('zip_invalid', $zip_file);
    }

    // Close zip file
    $zip->close();

    // Debug
    debug::add(2, tr("Successfully unpacked zip archive {1} to directory {2}", $zip_file, $dirname));

    // Return
    return true;

}
/**
 * Send a chunked file
 * 
 * @parma string $url The URL to send the file to.
 * @param string $filename The full path of the file to send.
 * @param string $remote_filename The remote filename to send with the request
 */
public function send_chunked_file(string $url, string $filename, string $remote_filename)
{

    // Ensure file exists
    if (!file_exists($filename)) { 
        throw new IOException('file_not_exists', $filename);
    }

    // Get size of file
    $size = filesize($filename);
    $total_chunks = ceil($size / 524288);

    // Get URL
    $url = rtrim($url, '/') . '/upload_chunk/' . $remote_filename . '/';

    // Open file
    if (!$fh = fopen($filename, 'rb')) { 
        throw new IOException('file_no_read', $filename);
    }

    // Set variables
    $count = 0;
    $chunk_num = 1;
    $contents = '';

    // Go through file
    while ($buffer = fread($fh, 1024)) { 
        $contents .= $buffer;
        $count++;

        // Send request, if needed
        if ($count >= 512) { 

            // Set request
            $request = array(
                'contents' => base64_encode($contents)
            );

            // Send http request
            $response = $this->send_http_request($url . $chunk_num . '/' . $total_chunks, 'POST', $request);

            // Update variables, as needed
                $contents = '';
                $count = 0;
                $chunk_num++;
        }

    }

    // Send last chunk, if needed
    if (!empty($contents)) {
        $request = array('contents' => base64_encode($contents));
        $this->send_http_request($url . $chunk_num . '/' . $total_chunks, 'POST', $request);
    }

    // Return
    return true;


}


}

