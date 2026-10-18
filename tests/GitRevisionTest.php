<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/GitRevision.php';
$base = sys_get_temp_dir() . '/lu-git-rev-' . bin2hex(random_bytes(5));
mkdir($base . '/.git/refs/heads', 0700, true);
$shaA=str_repeat('a',40);
$shaB=str_repeat('b',40);
$assert = static function(bool $ok,string $reason):void{
    if(!$ok){fwrite(STDERR,$reason."\n");exit(1);}
};
try {
  file_put_contents($base.'/.git/HEAD',"ref: refs/heads/main\n");
  file_put_contents($base.'/.git/refs/heads/main',$shaA."\n");
  $assert(GitRevision::read($base)===$shaA,'Loose ref not found.');
  unlink($base.'/.git/refs/heads/main');
  file_put_contents($base.'/.git/packed-refs',"# pack-refs\n".$shaB." refs/heads/main\n");
  $assert(GitRevision::read($base)===$shaB,'Packed ref not found.');
  file_put_contents($base.'/.git/HEAD',$shaA."\n");
  $assert(GitRevision::read($base)===$shaA,'Detached HEAD not supported.');
  file_put_contents($base.'/.git/HEAD',"ref: refs/heads/../../secrets\n");
  $assert(GitRevision::read($base)===null,'Path traversal should be rejected.');
  unlink($base.'/.git/HEAD');
  $assert(GitRevision::read($base)===null,'Missing HEAD should fail closed.');
  echo "GitRevision: OK\n";
} finally {
  @unlink($base.'/.git/HEAD');@unlink($base.'/.git/packed-refs');
  @rmdir($base.'/.git/refs/heads');@rmdir($base.'/.git/refs');
  @rmdir($base.'/.git');@rmdir($base);
}
