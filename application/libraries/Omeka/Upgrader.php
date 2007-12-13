<?php 
define('UPGRADE_DIR', APP_DIR.DIRECTORY_SEPARATOR.'migrations');
/**
 * This will be a wrapper for upgrade operations on Omeka.
 *
 * @package Omeka
 * @author CHNM
 **/
class Omeka_Upgrader
{
	protected $manager;
	protected $start;
	protected $end;
	protected $current;
 	const VERSION_OPTION = 'migration';
	
	public function __construct($fromVersion, $toVersion)
	{
		ini_set('max_execution_time', 0);
		
		$this->db = get_db();
		$this->start = $fromVersion;
		$this->end = $toVersion;
		
		//Display a nice omeka header for the upgrade tool
		$this->displayHeader();
		
		for ($i = $fromVersion+1; $i < $toVersion+1; $i++) { 
			
			//Start capturing the output
			ob_start();
			try {
				//Start the upgrade script
				$this->upgrade($i);
			} catch (Omeka_Db_Exception $e) {
				$db_exception = $e->getInitialException();
				
				$error = "Error in Migration #$i" . "\n\n";
				$error .= "Message: " . $db_exception->getMessage() . "\n\n"; 
				$error .= "Code: " . $db_exception->getCode() . "\n\n";
				$error .= "Line: " . $db_exception->getLine() . "\n\n";
				
				$upgrade_output = ob_get_contents();
				if($upgrade_output) {
					$error .= "Output from upgrade: ". $upgrade_output;
				}
					
				//If there was a problem with the upgrade, display the error message 
				//and email it to the administrator
				$email = get_option('administrator_email');
				
				$header = 'From: '.$email. "\n" . 'X-Mailer: PHP/' . phpversion();
				$title = "Omeka Upgrade Error";
				$body = "This error was thrown when attempting to upgrade your Omeka installation:\n\n" . $error;
				mail($email, $title, $body, $header);
				$this->displayError($error);
				
				
			}
			$this->current = $i;
			$this->incrementMigration();
			
			//Clean the contents of the output buffer
			echo ob_get_clean();
			
			if(!isset($error)) {
				$this->displaySuccess();
			}
			
			unset($error);
			
			
		}
		
		$this->displayFooter();
	}
	
	public function displayError($text) {
?>
	<p class="error">Omeka encountered an error when upgrading your installation.  The full text of this error has been emailed to your administrator:</p>
	
	<p class="error_text"><?php echo htmlentities($text); ?></p>
<?php 		
	}
	
	public function displaySuccess() {
		?>
		<p class="success">Successfully migrated #<?php echo $this->current; ?></p>
<?php		
	}
		
	public function displayHeader() {
		?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Upgrading Omeka</title>
<style type="text/css" media="screen">
	body {
		font: arial, sans-serif;
	}
	
</style>
</head>

<body>
	<h2 class="instruction">Omeka is now upgrading itself.  Please refresh your screen once this page finishes loading.</h2>	
<?php		
	}
	
	public function displayFooter() {
		?>
	</body>	
<?php		
	}
	
	public function upgrade($version) {
		//We may need to have a form or something in case the upgrade requires user input
		$formPath = UPGRADE_DIR.DIRECTORY_SEPARATOR.$version.DIRECTORY_SEPARATOR.'form.php';
		
		if(empty($_POST) and file_exists($formPath)) {
			include $formPath;
			exit;
		}
		
		$scriptPath = UPGRADE_DIR.DIRECTORY_SEPARATOR.$version.DIRECTORY_SEPARATOR.'upgrade.php';
		if(!file_exists($scriptPath)) {
			throw new Exception( 'Migration does not have any scripts associated with it!' );
		}
		
		include $scriptPath;
	}

	public function incrementMigration() {
		require_once 'Option.php';
		$optTable = $this->db->Option;
		$this->db->exec("UPDATE $optTable SET value = {$this->current} WHERE name = '" . self::VERSION_OPTION . "'");
	}
	
	public function hasTable($model) {
		$tbl = $this->db->$model;		
		$res = $this->query("SHOW tables LIKE '$tbl'");
		return !empty($res);
	}
	
	public function tableHasColumn($model, $column) {
		$col = $this->getColumnDefinition($tblName, $column);
		return !empty($col);
	}
	
	public function getColumnDefinition($table, $column) {
		//Replace with SHOW COLUMNS		
		
		$tblName = $this->db->$table;
		
		$explain = $this->query("EXPLAIN `$tblName`");
		foreach ($explain as $k => $col) {
			if($column == $col['Field'] ) {
				return $col;
			}
		}
		return false;
	}
} // END class Omeka_Upgrader 
?>