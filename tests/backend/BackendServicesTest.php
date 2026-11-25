<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/cms/backend/AppConfig.php';
require_once dirname(__DIR__,2).'/cms/backend/BackupService.php';
use Fecgd\Backend\AppConfig;
use Fecgd\Backend\BackupService;
use PHPUnit\Framework\TestCase;

final class BackendServicesTest extends TestCase
{
    private string $folder;
    private PDO $database;
    protected function setUp(): void {
        $this->folder=dirname(__DIR__,2).'/tmp/backend-tests-'.bin2hex(random_bytes(6));
        mkdir($this->folder.'/site/media',0700,true);mkdir($this->folder.'/site/dist/assets',0700,true);
        file_put_contents($this->folder.'/site/media/photo.jpg','image-fixture');
        file_put_contents($this->folder.'/site/media/video.mp4','video-fixture');
        file_put_contents($this->folder.'/site/media/secret.php','not-for-backup');
        file_put_contents($this->folder.'/site/dist/assets/logo.webp','logo-fixture');
        $this->database=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $this->database->exec('CREATE TABLE contents(id INTEGER PRIMARY KEY,title TEXT);INSERT INTO contents(title) VALUES("conservé")');
    }
    protected function tearDown(): void {
        foreach(['FECGD_PRIVATE_DIR','FECGD_BASE_URL','FECGD_ENV'] as $key)putenv($key);
        $base=realpath(dirname(__DIR__,2).'/tmp');$target=realpath($this->folder);
        if($base===false||$target===false||!str_starts_with($target,$base.DIRECTORY_SEPARATOR.'backend-tests-'))throw new RuntimeException('Nettoyage hors fixture refusé.');
        $files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->folder,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file){if($file->isDir())rmdir($file->getPathname());else unlink($file->getPathname());}rmdir($this->folder);
    }
    private function service(): BackupService {return new BackupService($this->database,$this->folder,$this->folder.'/private/backups');}
    public function testDefaultAndHostingConfiguration(): void {
        $config=AppConfig::fromEnvironment($this->folder);self::assertSame('/FECGD-DRC',$config->baseUrl);self::assertSame($this->folder.'/local-private/fecgd.sqlite',$config->databasePath());
        putenv('FECGD_BASE_URL=');putenv('FECGD_ENV=production');putenv('FECGD_PRIVATE_DIR='.$this->folder.'/private');
        $config=AppConfig::fromEnvironment($this->folder);self::assertSame('',$config->baseUrl);self::assertSame('production',$config->environment);self::assertSame($this->folder.'/private/backups',$config->backupDirectory());
    }
    public function testInvalidBaseUrlRejected(): void {$this->expectException(InvalidArgumentException::class);new AppConfig($this->folder,$this->folder,'//example.com','local');}
    public function testInvalidEnvironmentRejected(): void {$this->expectException(InvalidArgumentException::class);new AppConfig($this->folder,$this->folder,'','unknown');}
    public function testRelativePrivateDirectoryRejected(): void {$this->expectException(InvalidArgumentException::class);new AppConfig($this->folder,'../private','','local');}
    public function testBackupRestoresDatabaseAndVideosWithoutSourceChanges(): void {
        $before=$this->database->query('SELECT title FROM contents')->fetchColumn();$archive=$this->service()->create();
        self::assertSame(4,$this->service()->verify($archive));$zip=new ZipArchive();self::assertTrue($zip->open($archive));
        self::assertSame('video-fixture',$zip->getFromName('site/media/video.mp4'));self::assertFalse($zip->getFromName('site/media/secret.php'));
        $restored=$zip->getFromName('database/fecgd.sqlite');self::assertIsString($restored);file_put_contents($this->folder.'/restore.sqlite',$restored);$zip->close();
        $db=new PDO('sqlite:'.$this->folder.'/restore.sqlite');self::assertSame('ok',$db->query('PRAGMA quick_check')->fetchColumn());self::assertSame($before,$db->query('SELECT title FROM contents')->fetchColumn());$db=null;
        self::assertSame($before,$this->database->query('SELECT title FROM contents')->fetchColumn());
        self::assertSame([],glob(dirname($archive).'/*.partial.zip'));self::assertSame([],glob(dirname($archive).'/*.sqlite'));
        self::assertFileExists(dirname($archive).'/.htaccess');
    }
    public function testIndependentBackupsDoNotOverwriteEachOther(): void {self::assertNotSame($this->service()->create(),$this->service()->create());}
    public function testMissingOptionalMediaDirectory(): void {self::assertSame(1,(new BackupService($this->database,$this->folder,$this->folder.'/private/backups',['absent']))->verify((new BackupService($this->database,$this->folder,$this->folder.'/private/backups',['absent']))->create()));}
    public function testActiveTransactionRejected(): void {$this->database->beginTransaction();$this->expectException(RuntimeException::class);$this->service()->create();}
    public function testUnreadableArchiveRejected(): void {$this->expectException(RuntimeException::class);$this->service()->verify($this->folder.'/absent.zip');}
    public function testMissingManifestRejected(): void {$zip=new ZipArchive();$p=$this->folder.'/bad.zip';$zip->open($p,ZipArchive::CREATE);$zip->addFromString('dummy','test');$zip->close();$this->expectException(RuntimeException::class);$this->service()->verify($p);}
    public function testModifiedMediaRejected(): void {$p=$this->service()->create();$zip=new ZipArchive();$zip->open($p);$zip->addFromString('site/media/video.mp4','modified');$zip->close();$this->expectException(RuntimeException::class);$this->service()->verify($p);}
    public function testInvalidManifestRejected(): void {$zip=new ZipArchive();$p=$this->folder.'/bad.zip';$zip->open($p,ZipArchive::CREATE);$zip->addFromString('manifest.json','{"format":2,"files":[]}');$zip->close();$this->expectException(RuntimeException::class);$this->service()->verify($p);}
}
