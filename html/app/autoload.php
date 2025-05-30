<?php

function searchRecursive($dir, $file, $currentDepth = 0)
{
  $maxDepth = intval(MAX_FILE_DEPTH_RECURSIVE);
  if ($maxDepth === 0) $maxDepth = PHP_INT_MAX;

  // Stop searching if we reach the max depth
  if ($currentDepth > $maxDepth) {
    return false;
  }

  // Iterate over the directory contents
  foreach (scandir($dir) as $item) {
    if ($item === '.' || $item === '..') {
      continue; // Skip special directories
    }
    $path = $dir . DIRECTORY_SEPARATOR . $item;

    // If it's a directory, search it recursively
    if (is_dir($path)) {
      $result = searchRecursive($path, $file, $currentDepth + 1);
      if ($result) {
        return $result;
      }
    }
    // If it's a file and matches the target file, return the path
    if (is_file($path) && basename($path) === basename($file)) {
      return $path;
    }
  }

  return false; // File not found within the allowed depth
}

spl_autoload_register(function ($class) {
  // Prefix and base directory
  $prefix = 'App\\';
  $base_dir = realpath("./");

  // Check if the class uses the namespace prefix
  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) {
    // If the class name does not use the prefix, return
    return;
  }
  $namespaces = explode('\\', $class);
  if (count($namespaces) == 3) {
    // Replace namespace separators with directory separators
    $namespaces[1] = strtolower($namespaces[1]);
    $type = rtrim($namespaces[1], 's');
    $filename = "{$namespaces[2]}.$type.php";
    $search_dir = "$base_dir/{$namespaces[1]}";
    $file = searchRecursive($search_dir, $filename);
  } else {
    $file = $base_dir . '/app/' . $namespaces[1] . '.php';
  }  // Check if the file exists and require it
  if ($file && file_exists($file)) {
    require $file;
  }
});
