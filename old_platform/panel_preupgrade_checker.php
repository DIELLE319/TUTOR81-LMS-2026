#!/usr/local/psa/admin/bin/php
<?php
/**
 * Due to the changes in business model in PP10 release, not all previous accounts settings 
 * will be portable from the previous Plesk releases. Some business schemas will be lost for hosters 
 * and their clients. This tool could be launched prior to upgrade for the purpose of getting 
 * a report on potential problems with the upgrade. Based on the report a hoster can decide 
 * whether upgrade to PP10 is suitable for him.
 *
 * Requirements: script supports PHP4/PHP5 in case where installed PHP4 only
 */

define('APP_PATH', dirname(__FILE__));
define('DEBUG', 0); // allow to dump sql logs to output
define('PRE_UPGRADE_SCRIPT_VERSION', '12.5.30.0'); //script version
define('PLESK_VERSION', '12.5.30'); // latest Plesk version
@date_default_timezone_set(@date_default_timezone_get());

if (!defined('PHP_EOL')) {
    define('PHP_EOL', "\n", true);
}

define('LOG_PATH', APP_PATH . '/plesk_preupgrade_checker.log');

$phpType = php_sapi_name();
if (substr($phpType, 0, 3) == 'cgi') {
    //:INFO: set max execution time 1hr
    @set_time_limit(3600);
}

class Plesk10BusinessModel
{
    function validate()
    {
        if (!PleskInstallation::isInstalled()) {
            //:INFO: Plesk installation is not found. You will have no problems with upgrade, go on and install Plesk Panel 10
            return;
        }
    }

    function _getAdminEmail()
    {
    	$db = PleskDb::getInstance();
    	$sql = "SELECT val FROM misc WHERE param='admin_email'";
    	$adminEmail = $db->fetchOne($sql);

    	return $adminEmail;
    }

    //:INFO: Domain's type can be 'none','vrt_hst','std_fwd','frm_fwd'
    function _getDomainsByHostingType($type = 'vrt_hst')
    {
    	$db = PleskDb::getInstance();
    	$sql = 'SELECT name FROM domains WHERE htype="' . $type . '"';
    	$domains = $db->fetchAll($sql);

    	return $domains;
    }
}

class Plesk10Requirements
{
    function validate()
    {
        //:INFO: Make sure that offline management is switched off before upgrading to Plesk 10.x
        if (PleskInstallation::isInstalled() && (PleskVersion::is8x() || PleskVersion::is9x())) {
            $this->_checkOfflineManagement();
        }

        //:INFO: Check that Linux security module is swicthed off
        $this->_checkApparmorService();

        //:INFO: Server should have properly configured hostname and it should be resolved locally
        $this->_resolveHostname();

        //:INFO: Hard require for innodb turned on
        $this->_checkInnodbEngineTurnedOn();

		//:INFO: Validate PHP version for webmails
        $this->_checkPhpForWebmails();

        //:INFO: Avoid os system installed mod_security
        $this->_checkModSecurityNotInstalled();
    }


    function _checkModSecurityNotInstalled()
    {
        if (Util::isWindows()) {
            return;
        }

        $log = Log::getInstance('Checking that there is no system_mod_security installed');

        if (PleskOS::isDebLike()) {
            $modSecPkg = 'libapache2-modsecurity';
        } else if (PleskOS::isSuseLike()) {
            $modSecPkg = 'apache2-mod_security';
        } else { // RedHatLike
            $modSecPkg = 'mod_security';
        }
        $pleskCrsPkg = 'plesk-modsecurity-crs';

        $test = true;

        if (false !== PackageManager::isInstalled($modSecPkg)) {
            // mod_security is installed, but I can not understand that it system or shipped with plesk because it has same name.
            // Lets check in additional package plesk-modsecurity-crs. If it is not installed we assumes that mod_security is not from our distribution
            if (false === PackageManager::isInstalled($pleskCrsPkg)) {
                $test = false;
            }
        }

        if($test) {
            $log->resultOk();
            return;
        }

        $errMsg = "You have already installed {$modSecPkg} package which is not from Panel distribution." .PHP_EOL
                ."You should deinstall package {$modSecPkg} before component 'modsecurity' installation, otherwise your apache web-server will be broken."
				." Check KB article for details http://kb.odin.com/122324";
        $log->warning($errMsg);
        $log->resultWarning();
    }

    function _checkInnodbEngineTurnedOn()
    {
        $log = Log::getInstance('Checking if the MySQL engine InnoDB is allowed');

        if (Util::isWindows()) {
            $log->resultOk();
            return;
        }

        $db = PleskDb::getInstance();
		$version = $db->fetchRow("SHOW VARIABLES LIKE 'version'");
		if (version_compare($version['Value'], '5.6.0', '>=')) {
			$log->resultOk();
			return;
		}
		
        $row = $db->fetchRow("SHOW VARIABLES LIKE 'have_innodb'");
        if (@array_key_exists ('Value', $row)) {
            $have_innodb = $row['Value'];
	    } else {
            $errMsg = 'Unable to find InnoDB engine support.';
            $log->warning($errMsg);
            $log->resultWarning();
            return;
        }

        if ($have_innodb == "YES") {
            $log->resultOk();
            return;
        }

        $errMsg = 'The InnoDB engine is not allowed by MySQL. Plesk upgrade is not possible.' .PHP_EOL
            .'Please remove the option "skip-innodb" from /etc/my.cnf (or /etc/mysql/my.cnf) and restart MySQL service. This will allow InnoDB and make the upgrade possible.';
        $log->error($errMsg);
        $log->resultError();
    }

    function _checkPhpForWebmails()
    {
		$log = Log::getInstance('Checking the compatibility of the installed PHP version with the webmail software');

        if (Util::isWindows()) {
            $log->resultOk();
            return;
		}

		$phpWarn = 'After the Plesk upgrade, the Plesk-managed webmail software will require the following PHP versions: RoundCube - PHP 5.2 or later, Horde - PHP 5.3 or later, AtMail - any PHP version. Note that your version of PHP might become incompatible with the Plesk-managed webmail afer the upgrade - in this case, you can switch to AtMail or upgrade PHP.';
        if (false === PackageManager::isInstalled('psa-atmail')) {
			$phpWarn .= PHP_EOL . 'AtMail is not installed on your server.';

			if (version_compare('11.5.21', PleskVersion::getVersion(), '>')) {
				$phpWarn .= ' You can install the AtMail webmail before the Plesk upgrade. As an alternative, you can install a newer PHP version that will be compatible with your webmail after the upgrade.';
			}
		} else {
			$phpWarn .= PHP_EOL . 'AtMail is installed on your server.';
		}

        $phpVersion = PleskComponent::getPackageVersion('php');
        if (null == $phpVersion) {
            $log->warning($phpWarn . PHP_EOL . 'Unable to determine the installed PHP version.');
            $log->resultWarning();
            return;
		}
        $phpWarn .= PHP_EOL . "The installed PHP version is {$phpVersion}";

        $hordeSatisfiedPhp = true;
        if (false !== PackageManager::isInstalled('psa-horde') && version_compare('5.3.0', $phpVersion, '>')) {
            $phpWarn .= PHP_EOL . 'After the upgrade you will not be able to use the Horde webmail because it requires PHP 5.3 or later.';
            $hordeSatisfiedPhp = false;
        }

        $roundCubeSatisfiedPhp = true;
        if (false !== PackageManager::isInstalled('plesk-roundcube') && version_compare('5.2.0', $phpVersion, '>')) {
            $phpWarn .= PHP_EOL . 'After the upgrade you will not be able to use the RoundCube webmail because it requires PHP 5.2 or later.';
            $roundCubeSatisfiedPhp = false;
		}

		if ($hordeSatisfiedPhp && $roundCubeSatisfiedPhp) {
			$log->info($phpWarn);
			$log->resultOk();
		} else {
			$log->warning($phpWarn);
			$log->resultWarning();
		}
    }

    function _checkApparmorService()
    {
        if (Util::isLinux()) {
            $log = Log::getInstance('Detecting if the apparmor service is switched off');

            $apparmorPath = '/etc/init.d/apparmor';
            if (file_exists($apparmorPath)) {
            	$apparmor_status = Util::exec('/etc/init.d/apparmor status', $code);
            	if (preg_match('/(complain|enforce)/', $apparmor_status)) {
                	$warn = 'The \'Apparmor\' security module for the Linux kernel is turned on. ';
                	$warn .= 'Turn the module off before continuing work with Parallels Plesk Panel. Please check http://kb.odin.com/en/112903 for more details.';
                	$log->warning($warn);
                	$log->resultWarning();
                	return;
            	}
            }
            $log->resultOk();
        }
    }

    function _checkOfflineManagement()
    {
        $log = Log::getInstance('Detect virtualization');

        //:INFO: There is no ability to detect offline management inside VZ container
        if (Util::isVz()) {
            $warn = 'Virtuozzo is detected in your system. ';
            $warn .= 'Make sure that offline management is switched off for the container before installing or upgrading to ' . PleskVersion::getLatestPleskVersionAsString();
            $log->info($warn);

            return;
        }

        $log->resultOk();
    }

    function _resolveHostname()
    {
        $log = Log::getInstance('Validate hostname');

        $hostname = Util::getHostname();

        //Get the IPv address corresponding to a given Internet host name
        $ip = gethostbyname($hostname);
        if (!PleskValidator::isValidIp($ip)) {
            $warn = "Hostname '{$hostname}' is not resolved locally.";
            $warn .= 'Make sure that server should have properly configured hostname and it should be resolved locally before installing or upgrading to Plesk Billing';
            $log->warning($warn);
            $log->resultWarning();

            return;
        }
        $log->resultOk();
    }
}

class Plesk10MailServer
{
    function validate()
    {
        if (!PleskInstallation::isInstalled()) {
            return;
        }

        $this->_checkPostfixDnsLookup();
    }

    function _checkPostfixDnsLookup()
    {

        if (Util::isLinux()) {
            $log = Log::getInstance('Detecting if mail server type is Postfix');

            if ($this->_isPostfix()) {
                $log->info('Postfix is current mail server');
                $cmd = Util::lookupCommand('postconf', true) . ' disable_dns_lookups';
                $output = Util::exec($cmd, $code);
                if (preg_match('/disable_dns_lookups[\s]{0,}=[\s]{0,}yes/', $output)) {
                    $warn = "Parameter 'disable_dns_lookups' is disabled in Postfix configuration (/etc/postfix/main.cf). ";
                    $warn .= "By default this parameter is set to 'no' by Parallels Plesk. ";
                    $warn .= "Need to set param value disable_dns_lookups=yes";
                    $log->warning($warn);
                    $log->resultWarning();

                    return;
                }
            } else {
                $log->info('Qmail is current mail server');
            }

            $log->resultOk();
        }
    }

    function _isPostfix()
    {
        $res = Util::lookupCommand('postconf');

        return $res;
    }

    function CurrentWinMailServer()
    {
    	if (Util::isWindows()) {
    		$currentMailServer = Util::regQuery('\PLESK\PSA Config\Config\Packages\mailserver', '/ve', true);
            $log = Log::getInstance();
    		$log->info('Current mail server is: ' . $currentMailServer);
    		return $currentMailServer;
    	}
    }

}

class Plesk10Skin
{
    function validate()
    {
        if (!PleskInstallation::isInstalled()) {
            //:INFO: Plesk installation is not found. You will have no problems with upgrade, go on and install Plesk Panel 10
            return;
        }
    }
}

class Plesk10Permissions
{
    function validate()
    {
        //:INFO: FIXED in 11.0.0  $this->_validatePHPSessionDir(); // TP BUG 92004
    }

    function _validatePHPSessionDir()
    {
        if (Util::isLinux()) {
            $log = Log::getInstance('Validating permissions of the PHP session directory');

            $phpbinary = Util::getSettingFromPsaConf('CLIENT_PHP_BIN');
            $cmd = $phpbinary . " -r 'echo ini_get(\"session.save_path\");' 2>/dev/null";
            $path = Util::exec($cmd, $code);

            $cmd = 'su nobody -m -c "' . $phpbinary . ' -r \'@session_start();\' 2>&1"';
            $realResult = `$cmd`;

            $log->info("session.save_path = $path");

            if (!file_exists($path)) {
                // TODO no need to fail in this case, right?
                //$log->warning("No such directory {$path}");
                //$log->resultWarning();
                $log->info("No such directory '{$path}'");
                $log->resultOk();
                return;
            }

            $perms = (int)substr(decoct( fileperms($path) ), 2);
            $log->info('Permissions: '   . $perms);

            //:INFO: PHP on domains running via CGI/FastCGI can't use session by default http://kb.odin.com/en/7056
            if (preg_match('/Permission\sdenied/', $realResult, $match)) {
            	$warn = "If a site uses default PHP settings and PHP is in CGI/FastCGI mode, site applications are unable to create user sessions. This is because the apps run on behalf of a subscription owner who does not have permissions to the directory which stores session files, " . $path . ". Please check http://kb.odin.com/en/7056 for more details.";
            	$log->warning($warn);
            	$log->resultWarning();
            	return;
            }

            $log->resultOk();
        }
    }
}

class AutoinstallerKnownIssues
{
	function validate()
	{
		if (Util::isLinux()) {
			$this->_checkMixedPhpPackages();
		}
	}

	function _checkMixedPhpPackages()
	{
		if (PleskOS::isRedHatLike()) {
			$log = Log::getInstance("Checking that a mixed set of 'php' and 'php53' packages is not installed");

			$packages = PackageManager::listInstalled('php*', '/^php(53)?(-(common|devel|cli|mysql|sqlite2?|pdo|gd|imap|mbstring|xml))?$/');

			if ($packages === false) {
				$log->info("Failed to fetch php packages list from system package manager");
				return;
			}

			$hasPhp5 = $hasPhp53 = false;
			foreach ($packages as $package) {
				$name = $package['name'];
				$hasPhp5  |= ($name == 'php' || strpos($name, 'php-') === 0);
				$hasPhp53 |= (strpos($name, 'php53') === 0);
			}

			if ($hasPhp5 && $hasPhp53) {
				// We don't check for psa-php53?-configurator because a proper one will be installed depending on currently installed php(53)? package
				$warn = "You have a mixed set of 'php' and 'php53' packages installed. Installation or upgrade may fail or produce unexpected results. To resolve this issue run \"sed -i.bak -e '/^\s*skip-bdb\s*$/d' /etc/my.cnf ; yum update 'php*' 'mysql*'\".";
				$log->warning($warn);
				$log->resultWarning();
			} else {
				$log->resultOk();
			}
		}
	}
}

class Plesk10KnownIssues
{
    function validate()
    {
        if (!PleskInstallation::isInstalled()) {
            //:INFO: Plesk installation is not found. You will have no problems with upgrade, go on and install Plesk 10
            return;
        }

        //:INFO: Validate known OS specific issues with recommendation to avoid bugs in Plesk
        if (Util::isLinux()
            && Util::isVz()
            && Util::getArch() == 'x86_64'
            && PleskOS::isSuse103()
        ) {
            $this->_diagnoseKavOnPlesk10x();
        }

        if (Util::isLinux()) {

			$this->_checkMainIP(); //:INFO: Checking for main IP address http://kb.odin.com/en/112417
			$this->_checkCBMrelayURL(); //:INFO: Checking for hostname in Customer & Business Manager relay URL http://kb.odin.com/en/111500
			$this->_checkMySQLDatabaseUserRoot(); //:INFO: Plesk user "root" for MySQL database servers have not access to phpMyAdmin http://kb.odin.com/en/112779
			$this->_checkPAMConfigsInclusionConsistency();  //:INFO: Check that non-existent PAM services are not referenced by other services, i.e. that PAM configuration array is consistent http://kb.odin.com/en/112944
			//:INFO: FIXED in 11.0.0 $this->_checkZendExtensionsLoadingOrder(); //:INFO:#100253 Wrong order of loading Zend extensions ionCube declaraion in php.ini can cause to Apache fail http://kb.odin.com/en/1520
			$this->_checkDumpTmpDvalue(); //:INFO: #101168 If DUMP_TMP_D in /etc/psa/psa.conf contains extra space character at the end of string backup procedure will fails permanently http://kb.odin.com/113474
			$this->_checkProftpdIPv6(); //:INFO: #94489 FTP service proftpd cannot be started by xinetd if IPv6 is disabled http://kb.odin.com/en/113504
			//:INFO: FIXED in 11.0.0 $this->_checkModificationOfCustomizedDNSzones(); //:INFO: # http://kb.odin.com/en/113725
			//:INFO: FIXED in 11.0.0 $this->_checkBindInitScriptPermission(); //:INFO: #105806 If there is no execute permission on named(bind) init file upgrade will fail
			$this->_checkMysqlclient15Release(); //:INFO: #105256 http://kb.odin.com/en/113737
			$this->_checkNsRecordInDnsTemplate(); //:INFO: #94544  http://kb.odin.com/en/113119
			$this->_checkMysqlOdbcConnectorVersion(); //:INFO: #102516 http://kb.odin.com/en/113620
			$this->_checkSwCollectdIntervalSetting(); //:INFO: #105405 http://kb.odin.com/en/113711
            $this->_checkApacheStatus();
			$this->_checkClientPasswordInMyCnf(); //:INFO: #PPPM-1153
			$this->_checkForCryptPasswordsForMailAccounts(); //:INFO: PPPM-1405 http://kb.odin.com/120292
			$this->_checkImmutableBitOnPleskFiles(); //:INFO: #128414 http://kb.odin.com/115457
			$this->_checkAbilityToChownInDumpd(); //:INFO: #138655 http://kb.odin.com/en/116353
			$this->_checkShmOpenIssue(); //:INFO: #PPP-10228 http://kb.odin.com/121714
			$this->_checkTmpFreeDiskSpace(); //:INFO: RT#1964129

			if (PLeskVersion::is10x()) {
				$this->_checkIpAddressReferenceForForwardingDomains(); //:INFO: #72945 Checking for IP address references in Plesk database http://kb.odin.com/en/113475
			}

			if (PleskVersion::is10_0()) {
				$this->_oldBackupsRestoringWarningAfterUpgradeTo11x(); //:INFO: #58303  http://kb.odin.com/en/114041
			}

			if (PleskVersion::is10_1_or_below()) {
				$this->_checkCustomizedCnameRecordsInDnsTemplate(); //:INFO: Customized CNAME records in server's DNS template could lead to misconfiguration of BIND http://kb.odin.com/en/113118
			}

			if (PleskVersion::is10_2_or_above()) {
				$this->_checkSsoStartScriptsPriority(); //:INFO: Checking for conflicting of SSO start-up scripts http://kb.odin.com/en/112666
				$this->_checkIpcollectionReference(); //:INFO: #72751 http://kb.odin.com/en/113826
			}

			if (PleskVersion::is10_3_or_above()) {
				$this->_checkApsApplicationContext(); //:INFO: Broken contexts of the APS applications can lead to errors at building Apache web server configuration  http://kb.odin.com/en/112815
				$this->_checkApsTablesInnoDB();
			}

			if (!PleskVersion::is10_4_or_above()) {
				$this->_checkCustomPhpIniOnDomains(); //:INFO: Check for custom php.ini on domains http://kb.odin.com/en/111697
				$this->_checkCustomVhostSkeletonStatisticsSubdir();
				
			}
			
			if (PleskVersion::is10_4_or_above()) {
				$this->_checkCustomWebServerConfigTemplates(); //:INFO: #PPPM-1195 http://kb.odin.com/118192
			}
			
        	if (PleskOS::isDebLike()) {
        		$this->_checkSymLinkToOptPsa(); //:INFO: Check that symbolic link /usr/local/psa actually exists on Debian-like OSes http://kb.odin.com/en/112214
        	}

			if (PleskOS::isRedHatLike()) {
				$this->_checkMailDriversConflictWithSendmail(); //:INFO: #PPPM-1082 http://kb.odin.com/118841
			}
			
			if (PleskOS::isCentOS5()) {
				$this->_checkMixedPackagesPhp5Configurators(); //:INFO: #PPPM-1658 http://kb.odin.com/
			}
			
        	if (Util::isVz()) {
        		$this->_checkUserBeancounters(); //:INFO: Checking that limits are not exceeded in /proc/user_beancounters http://kb.odin.com/en/112522
				$this->_checkShmPagesLimit(); //:INFO: PSA service does not start. Unable to allocate shared memory segment. http://kb.odin.com/122412
				
				if (PleskOS::isRedHatLike()) {
					$this->_checkMailDriversConflict(); //:INFO: #PPPM-955 http://kb.odin.com/120284
				}
            }

            if (PleskVersion::is_below_12_1()) {
                $this->_checkDbUsersTableCollation();
            }
        }

		$this->_checkForCryptPasswords();
		//:INFO: FIXED in 11.0.0 $this->_checkForCryptPasswords(); //:INFO: http://kb.odin.com/en/112391
		$this->_checkMysqlServersTable(); //:INFO: Checking existing table mysql.servers
		//:INFO: FIXED in 11.5.30	   $this->_checkUserHasSameEmailAsAdmin(); //:INFO: If user has the same address as the admin it should be changed to another http://kb.odin.com/en/111985
		//:INFO: FIXED in 11.0.0       $this->_checkDefaultMySQLServerMode(); //:INFO:#66278,70525 Checking SQL mode of default client's MySQL server http://kb.odin.com/en/112453
		//:INFO: FIXED in 10.4.4 MU#19 $this->_checkUserHasSameEmailAsEmailAccount(); //:INFO: Users with same e-mails as e-mail accounts will have problems with changing personal contact information http://kb.odin.com/en/112032
		$this->_checkPleskTCPPorts(); //:INFO: Check the availability of Plesk TCP ports http://kb.odin.com/en/391
		$this->_checkFreeMemory(); //:INFO: Check for free memory http://kb.odin.com/en/112522
		$this->_checkPanelAccessForLocalhost(); //:INFO: Upgrade of Customer & Business Manager failed in case of 127.0.0.1 is restricted for administrative access http://kb.odin.com/en/113096
		//:INFO: FIXED in 11.0.0       $this->_checkCustomDNSrecordsEqualToExistedSubdomains(); //:INFO: Customized DNS records with host equal host of existing subdomain will lost after upgrade to Plesk version above 10.4.4 http://kb.odin.com/en/113310
		//:INFO: FIXED in 11.0.0 	   $this->_checkForwardingURL(); //:INFO: Wrong GUI behavior if forwarding URL hasn't "http://" after upgrade to Plesk version above 10.4.4 http://kb.odin.com/en/113359

		if (PleskVersion::is10x()
			&& !PleskVersion::is10_2_or_above()
		) {
			$this->_checkCBMlicense(); //:INFO: Check for Customer and Business Manager license http://kb.odin.com/en/111143
		}

		if (PleskVersion::is10_4()) {
			$this->_notificationSubDomainsHaveOwnDNSZoneSince104(); //:INFO: Notification about after upgrade all subdomains will have own DNS zone http://kb.odin.com/en/112966
		}

        if (PleskVersion::is_below_12_0()) {
            if (Util::isWindows()) {
                $this->_unsupportedClamAvWarningOnUpgradeTo12_0x(); //:INFO: Plesk 11.6.3 version does not support ClamAV Antivirus
                $this->_unsupportedUrchinWarningOnUpgradeTo12_0x(); //:INFO: Plesk 11.6.3 version does not support Urchin web statistics
                $this->_unsupportedMla2000WarningOnUpgradeTo12_0x(); //:INFO: Plesk 11.6.4 version does not support mylittleadmin 2000
            }
        }

		if (Util::isWindows()) {
			$this->_checkPhprcSystemVariable(); //:INFO: #PPPM-294  Checking for PHPRC system variable
			$this->_unknownISAPIfilters(); //:INFO: Checking for unknown ISAPI filters and show warning http://kb.odin.com/en/111908
			$this->_checkMSVCR(); //:INFO: Just warning about possible issues related to Microsoft Visual C++ Redistributable Packages http://kb.odin.com/en/111891
			// Probably fixed in PPP-10378 $this->_checkConnectToClientMySQL(); //:INFO: #81461 Checking possibility to connect to client's MySQL server http://kb.odin.com/en/111983
			$this->_checkIisFcgiDllVersion(); //:INFO: Check iisfcgi.dll file version http://kb.odin.com/en/112606
			$this->_checkCDONTSmailrootFolder(); //:INFO: After upgrade Plesk change permissions on folder of Collaboration Data Objects (CDO) for NTS (CDONTS) to default, http://kb.odin.com/en/111194
			$this->_checkNullClientLogin(); //:INFO: #118963 http://kb.odin.com/114835
			if (Util::isVz()) {
				$this->_checkDotNetFrameworkIssue(); //:INFO: Check that .net framework installed properly http://kb.odin.com/en/111448
			}
			if (PleskVersion::is10x()) {
				$this->_checkSmarterMailOpenPorts(); //:INFO: #98549 Plesk doesn't bind Smartermail 8 ports on new IPs http://kb.odin.com/en/113330
			}
            if (PleskVersion::is_below_12_1()) {
                $this->_unsupportedMSSQLForPleskInternalDbOnUpgradeTo12_1();
            }
        }
	}

	//:INFO: PSA service does not start. Unable to allocate shared memory segment. http://kb.odin.com/122412
	function _checkShmPagesLimit()
	{
		$log = Log::getInstance("Checking for limit shmpages...", true);
		$ubc = Util::getUserBeanCounters();
		if ((int)$ubc['shmpages']['limit'] < 40960) {
			$log->emergency("Parallels Virtuozzo Container set the \"shmpages\" limit to {$ubc['shmpages']['limit']}, which is too low. This may cause the sw-engine service not to start. To resolve this issue, refer to the article at http://kb.odin.com/122412");
			$log->resultWarning();
			return;
		}

		$log->resultOk();
	}

	//:INFO: RT#1964129 
	function _checkTmpFreeDiskSpace()
	{
		$log = Log::getInstance("Checking for free space in /tmp...", true);
		
		$tmpdf = disk_free_space("/tmp");

		if ($tmpdf < 100000000) {
			$log->emergency('Available disk space in the /tmp directory is less than 100 MB. This may lead to upgrade failure. Please free up disk space in the /tmp directory.');
			$log->resultWarning();
			return;
		}
		
		$log->resultOk();
	}
	
	//:INFO: #PPPM-294
	function _checkPhprcSystemVariable()
	{
		$log = Log::getInstance("Checking for PHPRC system variable...", true);
		
		$phprc = getenv('PHPRC');

		if ($phprc) {
			$log->emergency('The environment variable PHPRC is present in the system. This variable may lead to upgrade failure. Please delete this variable from the system environment.');
			$log->resultWarning();
			return;
		}
		
		$log->resultOk();
	}
	
	//:INFO: #PPPM-1658 http://kb.parallels.com/121779
	function _checkMixedPackagesPhp5Configurators()
	{
		$log = Log::getInstance("Checking if psa-php53-configurator and psa-php5-configurator packages are installed", true);
		$php53 = PackageManager::isInstalled('psa-php53-configurator');
		$php5 = PackageManager::isInstalled('psa-php5-configurator');
		
		if ($php53 && $php5) {
			$log->emergency('Both psa-php53-configurator and psa-php5-configurator packages are installed. This will cause Plesk upgrade failure. To resolve this issue, refer to the article at http://kb.odin.com/121779');
			$log->resultWarning();
			return;
		}
		
		$log->resultOk();
	}
	
	//:INFO: #PPP-10228 http://kb.odin.com/121714
    function _checkShmOpenIssue()
	{
		$log = Log::getInstance("Checking the shm_open issue", true);
		
		$scriptName = APP_PATH . '/shm_open_issue_test.py';
		$scriptContent = "from multiprocessing import Lock\nlock = Lock()";
		
		file_put_contents($scriptName, $scriptContent);
		$out=shell_exec("python {$scriptName} 2>&1 1>/dev/null");
		if (stristr($out, 'Function not implemented')) {
			$log->emergency('After upgrade to Plesk 12, access to Plesk will be unavailable. Users might encounter the error "Function not implemented". To resolve this issue, refer to http://kb.odin.com/121714');
			$log->resultWarning();
			return;
		}
		
		$log->resultOk();
	}

	//:INFO: #138655 http://kb.odin.com/116353
    function _checkAbilityToChownInDumpd()
	{
		$log = Log::getInstance("Checking the possibility to change the owner of files in the DUMP_D directory", true);
		
		$dump_d = Util::getSettingFromPsaConf('DUMP_D');
		if (is_null($dump_d)) {
			$log->warning('Unable to obtain the path to the directory defined by the DUMP_D parameter. Check that the DUMP_D parameter is set in the /etc/psa/psa.conf file.');
			$log->resultWarning();
			return;
		}
		
		$file = $dump_d . '/pre_upgrade_test_checkAbilityToChownInDumpd';
		
		if (false === file_put_contents($file, 'test')) {
			$log->emergency('Unable to write in the ' . $dump_d . ' directory. The upgrade procedure will fail. Check that the folder exists and you have write permissions for it, and repeat upgrading. ');
			$log->resultWarning();
			return;
		} else {
			$chown_result = @chown($file, 'root');
			$chgrp_result = @chgrp($file, 'root');
			unlink($file);
			if (!$chown_result 
				|| !$chgrp_result) {
				$log->emergency('Unable to change the owner of files in the ' . $dump_d . ' directory. The upgrade procedure will fail. Please refer to http://kb.odin.com/116353 for details.');
				$log->resultError();
				return;
			}
		}
		$log->resultOk();
	}

	//:INFO: #128414 http://kb.odin.com/115457
    function _checkImmutableBitOnPleskFiles()
    {
    	$log = Log::getInstance("Checking Panel files for the immutable bit attribute");
    	
    	$cmd = 'lsattr -R /usr/local/psa/ 2>/dev/null |awk \'{split($1, a, ""); if (a[5] == "i") {print;}}\'';
    	$output = Util::exec($cmd, $code);
    	$files = explode('\n', $output);
    	
    	if (!empty($output)) {
    		$log->info('The immutable bit attribute of the following Panel files can interrupt the upgrade procedure:');
    		foreach ($files as $file) {
    			$log->info($file);
    		}
    		$log->emergency('Files with the immutable bit attribute were found. Please check http://kb.odin.com/115457 for details.');
    		$log->resultWarning();
    		return;
    	}
    	
    	$log->resultOk();
    }
	
	//:INFO: #PPPM-1195 http://kb.odin.com/118192
	function _checkCustomWebServerConfigTemplates()
	{
		$log = Log::getInstance("Checking for custom web server configuration templates");
		$pleskDir = Util::getSettingFromPsaConf('PRODUCT_ROOT_D');
		$customTemplatesPath = $pleskDir . '/admin/conf/templates/custom';
		
		if (is_dir($customTemplatesPath)) {
			$log->warning("There are custom web server configuration templates at ${customTemplatesPath}. These custom templates might be incompatible with a new Plesk version, and this might lead to failure to generate web server configuration files."
					. "Please check http://kb.odin.com/118192 for details.");
			$log->resultWarning();
			return;
		}
		$log->resultOk();
	}
	
	//:INFO: #PPPM-955 http://kb.odin.com/120284
	function _checkMailDriversConflict()
	{
		$log = Log::getInstance("Checking for a Plesk mail drivers conflict");
		
		if (((true === PackageManager::isInstalled('psa-mail-pc-driver') || true === PackageManager::isInstalled('plesk-mail-pc-driver')) 
		&& true === PackageManager::isInstalled('psa-qmail'))
		|| ((true === PackageManager::isInstalled('psa-mail-pc-driver') || true === PackageManager::isInstalled('plesk-mail-pc-driver'))
		&& true === PackageManager::isInstalled('psa-qmail-rblsmtpd'))) {
			$log->warning("Plesk upgrade by EZ templates failed if psa-mail-pc-driver and psa-qmail or psa-qmail-rblsmtpd packages are installed. "
					. "Please check http://kb.odin.com/120284 for details.");
			$log->resultWarning();
			return;
		}

		$log->resultOk();
	}
	
	//:INFO: #PPPM-1082 http://kb.odin.com/118841
	function _checkMailDriversConflictWithSendmail()
	{
		$log = Log::getInstance("Checking for a Plesk mail drivers conflict with sendmail");
		
		if ((true === PackageManager::isInstalled('plesk-mail-pc-driver') 
		|| true === PackageManager::isInstalled('psa-mail-pc-driver'))
		&& (true === PackageManager::isInstalled('sendmail-doc') 
		|| true === PackageManager::isInstalled('sendmail-cf')
		|| true === PackageManager::isInstalled('sendmail'))) {
			$log->warning("Plesk upgrade by EZ templates failed if plesk-mail-pc-driver or psa-mail-pc-driver are installed and installed any of following packages: "
					. "sendmail-doc, sendmail-cf, sendmail. Please check http://kb.odin.com/118841 for details.");
			$log->resultWarning();
			return;
		}

		$log->resultOk();
	}
	
	
	//:INFO: #PPPM-1153
	function _checkClientPasswordInMyCnf()
	{
		$log = Log::getInstance("Checking for a password in my.cnf files");
		
		$mycnf_files = array('/root/.my.cnf', '/etc/my.cnf', '/etc/mysql/my.cnf', '/var/db/mysql/my.cnf');
		foreach ($mycnf_files as $mycnf) {
			if (is_file($mycnf)) {
				$mycnf_content = Util::readfileToArray($mycnf);
				if ($mycnf_content) {
					foreach($mycnf_content as $line) {
						if (preg_match('/^password(\s+)?\=/si', $line, $match)) {
							$log->emergency("The file $mycnf contains a password for the MySQL console client. Please remove this file temporarily and restore it after the upgrade, otherwise the upgrade will fail.");
							$log->resultWarning();
							return;
						}
					}
				}
			}
		}
    	$log->resultOk();
	}
	
	//:INFO: #118963 http://kb.odin.com/114835
	function _checkNullClientLogin()
	{
		$log = Log::getInstance("Checking for accounts with empty user names");
		
		$mysql = PleskDb::getInstance();
    	$sql = "SELECT domains.id, domains.name, clients.login FROM domains LEFT JOIN clients ON clients.id=domains.cl_id WHERE clients.login is NULL";
    	$nullLogins = $mysql->fetchAll($sql);

    	if (!empty($nullLogins)) {
    		$log->warning('There are accounts with empty user names. This problem can cause the backup or migration operation to fail. Please see http://kb.odin.com/en/114835 for the solution.');
    		$log->resultWarning();
    		return;
    	}

    	$log->resultOk();
	}

    //:INFO: #58303 http://kb.odin.com/en/114041
    function _oldBackupsRestoringWarningAfterUpgradeTo11x()
    {
        $log = Log::getInstance();
    	$log->warning('Error messages can appear while restoring backups created in Panel 10.0.1. See http://kb.odin.com/en/114041 for details.');
    	$log->resultWarning();
    }

    //:INFO: #105405 http://kb.odin.com/en/113711
    function _checkSwCollectdIntervalSetting()
    {
    	$log = Log::getInstance("Checking the 'Interval' parameter in the sw-collectd configuration file");

    	$collectd_config = '/etc/sw-collectd/collectd.conf';
    	if (file_exists($collectd_config)) {
    		if (!is_file($collectd_config) || !is_readable($collectd_config))
    		return;

    		$config_content = Util::readfileToArray($collectd_config);
    		if ($config_content) {
    			foreach ($config_content as $line) {
    				if (preg_match('/Interval\s*\d+$/', $line, $match)) {
    					if (preg_match('/Interval\s*10$/', $line, $match)) {
    						$log->warning('If you leave the default value of the "Interval" parameter in the ' . $collectd_config . ', sw-collectd may heavily load the system. Please see http://kb.odin.com/en/113711 for details.');
    						$log->resultWarning();
    						return;
    					}
    					$log->resultOk();
    					return;
    				}
    			}
    			$log->warning('If you leave the default value of the "Interval" parameter in the ' . $collectd_config . ', sw-collectd may heavily load the system. Please see http://kb.odin.com/en/113711 for details.');
    			$log->resultWarning();
    			return;
    		}
    	}
    }

    private function _checkApacheStatus()
    {
        $log = Log::getInstance("Checking Apache status");

        $apacheCtl = file_exists('/usr/sbin/apache2ctl') ? '/usr/sbin/apache2ctl' : '/usr/sbin/apachectl';

        if (!is_executable($apacheCtl)) {
            return;
        }

        $resultCode = 0;
        Util::Exec("$apacheCtl -t 2>/dev/null", $resultCode);

        if (0 !== $resultCode) {
            $log->error("The Apache configuration is broken. Run '$apacheCtl -t' to see the detailed info.");
            $log->resultError();
            return;
        }

        $log->resultOk();
    }

    //:INFO: #94544  http://kb.odin.com/en/113119
    function _checkNsRecordInDnsTemplate()
    {
    	$log = Log::getInstance("Checking NS type records in the Panel DNS template");

    	$mysql = PleskDb::getInstance();
    	$sql = "SELECT 1 FROM dns_recs_t WHERE type='NS'";
    	$nsRecord = $mysql->fetchAll($sql);

    	if (empty($nsRecord)) {
    		$log->warning('There are no NS records in the Panel DNS template. This can break the BIND server configuration. Please see http://kb.odin.com/en/113119 for the solution.');
    		$log->resultWarning();
    		return;
    	}

    	$log->resultOk();
    }

    //:INFO: #102516 http://kb.odin.com/en/113620
    function _checkMysqlOdbcConnectorVersion()
    {
    	$log = Log::getInstance("Checking the version of MySQL ODBC package");
    	if (PleskOS::isRedHatLike() || PleskOS::isSuseLike()) {
    		$package = 'mysql-connector-odbc';
    	} else {
    		$package = 'libmyodbc';
    	}

    	$version = Package::getVersion($package);

    	if ($version === false) {
    		return;
    	}

    	if (preg_match('/\d+\.\d+\.\d+/', $version, $match) && version_compare($match[0], '3.51.21', '<')) {
    		$log->warning('The installed version of ' . $package . ' is outdated. Please see http://kb.odin.com/en/113620 for details.');
    		$log->resultWarning();
    		return;
    	}

    	$log->resultOk();
    }

    //:INFO: #72751  http://kb.odin.com/en/113826
    function _checkIpcollectionReference()
    {
    	$log = Log::getInstance("Checking consistency of the IP addresses list in the Panel database");

    	$mysql = PleskDb::getInstance();
    	$sql = "SELECT 1 FROM ip_pool, clients, IpAddressesCollections, domains, DomainServices, IP_Addresses WHERE DomainServices.ipCollectionId = IpAddressesCollections.ipCollectionId AND domains.id=DomainServices.dom_id AND clients.id=domains.cl_id AND ipAddressId NOT IN (select id from IP_Addresses) AND IP_Addresses.id = ip_pool.ip_address_id AND pool_id = ip_pool.id GROUP BY pool_id";
    	$brokenIps = $mysql->fetchAll($sql);
    	$sql = "select 1 from DomainServices, domains, clients, ip_pool where ipCollectionId not in (select IpAddressesCollections.ipCollectionId from IpAddressesCollections) and domains.id=DomainServices.dom_id and clients.id = domains.cl_id and ip_pool.id = clients.pool_id and DomainServices.type='web' group by ipCollectionId";
    	$brokenCollections = $mysql->fetchAll($sql);

    	if (!empty($brokenIps) || !empty($brokenCollections)) {
    		$log->warning('Some database entries related to Panel IP addresses are corrupted. Please see http://kb.odin.com/en/113826 for the solution.');
    		$log->resultWarning();
    		return;
    	}

    	$log->resultOk();
    }

    //:INFO: #105256 http://kb.odin.com/en/113737
    function _checkMysqlclient15Release()
    {
    	$log = Log::getInstance("Checking the version of the mysqlclient15 package");
    	if (PleskOS::isRedHatLike()) {
    		$release = Package::getRelease('mysqlclient15');

    		if ($release === false) {
    			return;
    		}

    		if (preg_match('/1\.el5\.art/', $release)) {
    			$log->emergency('The installed version of mysqlclient15 is outdated. This may lead to upgrade fail. Please see http://kb.odin.com/en/113737 for the solution');
    			$log->resultWarning();
    			return;
    		}
    	}
    	$log->resultOk();
    }

    //:INFO: #105806 If there is no execute permission on named(bind) init file upgrade will fail
    function _checkBindInitScriptPermission()
    {
    	$log = Log::getInstance("Checking permissions of the BIND initizalization script");

    	$redhat = '/etc/init.d/named';
    	$debian = '/etc/init.d/bind9';
    	$suse = '/etc/init.d/named';
    	$bindInitFile = 'unknown';

    	if (PleskOS::isRedHatLike()) {
    		$bindInitFile = $redhat;
    	}
    	if (PleskOS::isDebLike()) {
    		$bindInitFile = $debian;
    	}
    	if (PleskOS::isSuseLike()) {
    		$bindInitFile = $suse;
    	}

    	$perms = Util::exec('ls -l ' . $bindInitFile, $code);

    	if (!preg_match('/^.+x.+\s/', $perms)
    		&& $code === 0) {
    		$log->emergency('The ' . $bindInitFile . ' does not have the execute premission. This may lead to upgrade fail. Please see http://kb.odin.com/en/113733 for the solution.');
    		$log->resultWarning();
    		return;
    	}

    	$log->resultOk();
    }

    //:INFO: #101351 #101690 #103125 #104527 #104528 http://kb.odin.com/en/113725
    function _checkModificationOfCustomizedDNSzones()
    {
    	$log = Log::getInstance("Checking for user-modified DNS records that will be changed during the upgrade");

    	$mysql = PleskDb::getInstance();
    	if (PleskVersion::is10_2_or_above()) {
    		$ipv4 = "SELECT dns_zone.id, dns_zone.name, dns_recs.val, ip_address from dns_zone, dns_recs, DomainServices, IpAddressesCollections, IP_Addresses, domains where domains.dns_zone_id = dns_zone.id AND dns_zone.id = dns_recs.dns_zone_id AND dns_recs.type = 'A' AND dns_recs.host = concat(domains.name,'.') AND domains.id = DomainServices.dom_id AND DomainServices.type = 'web' AND DomainServices.ipCollectionId = IpAddressesCollections.ipCollectionId AND IpAddressesCollections.ipAddressId = IP_Addresses.id AND IP_Addresses.ip_address not like '%:%' AND dns_recs.val not like '%:%' AND IP_Addresses.ip_address <> dns_recs.val";
    		$ipv6 = "SELECT dns_zone.id, dns_zone.name, dns_recs.val, ip_address from dns_zone, dns_recs, DomainServices, IpAddressesCollections, IP_Addresses, domains where domains.dns_zone_id = dns_zone.id AND dns_zone.id = dns_recs.dns_zone_id AND dns_recs.type = 'A' AND dns_recs.host = concat(domains.name,'.') AND domains.id = DomainServices.dom_id AND DomainServices.type = 'web' AND DomainServices.ipCollectionId = IpAddressesCollections.ipCollectionId AND IpAddressesCollections.ipAddressId = IP_Addresses.id AND IP_Addresses.ip_address like '%:%' AND dns_recs.val like '%:%'  AND IP_Addresses.ip_address <> dns_recs.val";
    		$ipv4_zones = $mysql->fetchAll($ipv4);
    		$ipv6_zones = $mysql->fetchAll($ipv6);
    		$dns_zones = array_merge($ipv4_zones, $ipv6_zones);
    	} else {
    		$fwd = "SELECT dns_zone.id, dns_zone.name, dns_recs.val, ip_address AS hosts from dns_zone, dns_recs, forwarding, IP_Addresses, domains where domains.dns_zone_id = dns_zone.id AND dns_zone.id = dns_recs.dns_zone_id AND dns_recs.type = 'A' AND dns_recs.host = concat(domains.name,'.') AND domains.id = forwarding.dom_id AND forwarding.ip_address_id = IP_Addresses.id AND IP_Addresses.ip_address <> dns_recs.val";
    		$hst = "SELECT dns_zone.id, dns_zone.name, dns_recs.val, ip_address AS hosts from dns_zone, dns_recs, hosting, IP_Addresses, domains where domains.dns_zone_id = dns_zone.id AND dns_zone.id = dns_recs.dns_zone_id AND dns_recs.type = 'A' AND dns_recs.host = concat(domains.name,'.') AND domains.id = hosting.dom_id AND hosting.ip_address_id = IP_Addresses.id AND IP_Addresses.ip_address <> dns_recs.val";
    		$fwd_zones = $mysql->fetchAll($fwd);
    		$hst_zones = $mysql->fetchAll($hst);
    		$dns_zones = array_merge($fwd_zones, $hst_zones);
    	}

    	$warning = false;
    	foreach ($dns_zones as $zone) {
    		$subdomains = $mysql->fetchAll('SELECT subdomains.name FROM domains, subdomains WHERE subdomains.dom_id = domains.id AND domains.dns_zone_id=' . $zone['id']);
    		if (!empty($subdomains)) {
    			$log->info('The existing A and AAAA records in the DNS zone ' . $zone['name'] . ' will be modified or removed after the upgrade.');
    			$warning = true;
    		}
    	}

    	if ($warning) {
    		$log->warning('Some of the existing A or AAAA records in DNS zones will be modified or removed after the upgrade. Please see http://kb.odin.com/en/113725 and ' . LOG_PATH . ' for details.');
    		$log->resultWarning();
    		return;
    	}

    	$log->resultOk();
    }

    //:INFO: Broken contexts of the APS applications can lead to errors at building Apache web server configuration http://kb.odin.com/en/112815
    function _checkApsApplicationContext()
    {
    	$log = Log::getInstance("Checking installed APS applications");
    	$mysql = PleskDb::getInstance();
    	$sql = "SELECT * FROM apsContexts WHERE (pleskType = 'hosting' OR pleskType = 'subdomain') AND subscriptionId = 0";
    	$brokenContexts = $mysql->fetchAll($sql);

    	if (!empty($brokenContexts)) {
    		$log->warning('Some database entries realted to the installed APS applications are corrupted. Please see http://kb.odin.com/en/112815 for the solution.');
    		$log->resultWarning();
    		return;
    	}
    	$log->resultOk();
    }

    //:INFO: #98549 Plesk doesn't bind Smartermail 8 ports on new IPs http://kb.odin.com/en/113330
    function _checkSmarterMailOpenPorts()
    {
    	$log = Log::getInstance("Checking SmarterMail open ports");

    	if (Plesk10MailServer::CurrentWinMailServer() == 'smartermail') {
    		$ip_addresses = Util::getIPListOnWindows();

    		$mysql = PleskDb::getInstance();
    		$sql = "select ip_address from IP_Addresses";
    		$ip_addresses = $mysql->fetchAll($sql);

    		foreach ($ip_addresses as $ip) {
    			if (PleskValidator::validateIPv4($ip['ip_address'])) {
    				$fp = @fsockopen($ip['ip_address'], 25, $errno, $errstr, 1);
    			} elseif (PleskValidator::validateIPv6($ip['ip_address'])) {
    				$fp = @fsockopen('[' . $ip['ip_address'] . ']', 25, $errno, $errstr, 1);
    			} else {
    				$log->warning('The IP address is invalid: ' . $ip['ip_address']);
    				$log->resultWarning();
    				return;
    			}
    			if (!$fp) {
    				// $errno 110 means "timed out", 111 means "refused"
    				$log->info('Unable to connect to the SMTP port 25 on the IP address ' . $ip['ip_address'] . ': ' . $errstr);
    				$warning = true;
    			}
    		}
    		if ($warning) {
    			$log->warning('SmarterMail is unable to use some of the IP addresses because they are not associated with the SmarterMail ports. Please check http://kb.odin.com/en/113330 for details.');
    			$log->resultWarning();
    			return;
    		}
    	}

    	$log->resultOk();
    }

    //:INFO: #94489 FTP service proftpd cannot be started by xinetd if IPv6 is disabled http://kb.odin.com/en/113504
    function _checkProftpdIPv6()
    {
    	$log = Log::getInstance("Checking proftpd settings");

    	$inet6 = '/proc/net/if_inet6';
    	if (!file_exists($inet6) || !@file_get_contents($inet6)) {
			$proftpd_config = '/etc/xinetd.d/ftp_psa';
    		if (!is_file($proftpd_config) || !is_readable($proftpd_config))
    			return null;

    		$config_content = Util::readfileToArray($proftpd_config);
    		if ($config_content) {
    			for ($i=0; $i<=count($config_content)-1; $i++) {
    				if (preg_match('/flags.+IPv6$/', $config_content[$i], $match)) {
    					$log->warning('The proftpd FTP service will fail to start in case the support for IPv6 is disabled on the server. Please check http://kb.odin.com/en/113504 for details.');
    					$log->resultWarning();
    					return;
    				}
    			}
    		}
    	}
    	$log->resultOk();
    }

    //:INFO: #72945 Checking for IP address references in Plesk database http://kb.odin.com/en/113475
    function _checkIpAddressReferenceForForwardingDomains()
    {
    	$log = Log::getInstance("Checking associations between domains and IP addresses");
    	$mysql = PleskDb::getInstance();
    	if (PleskVersion::is10_2_or_above()) {
    		$sql = "SELECT * FROM IpAddressesCollections WHERE ipAddressId = 0";
    	} else {
    		$sql = "SELECT * FROM forwarding WHERE ip_address_id = 0";
    	}
    	$domains = $mysql->fetchAll($sql);

    	if (!empty($domains)) {
    		$log->warning('There is a number of domains which are not associated with any IP address. This may be caused by an error in the IP address database. Please check http://kb.odin.com/en/113475 for details.');
    		$log->resultWarning();
    		return;
    	}
    	$log->resultOk();
    }

    //:INFO: #101168 If DUMP_TMP_D in /etc/psa/psa.conf contains extra space character at the end of string backup procedure will fails permanently http://kb.odin.com/113474
    function _checkDumpTmpDvalue()
    {
    	$log = Log::getInstance("Checking the /etc/psa/psa.conf file for consistency");

    	$file = '/etc/psa/psa.conf';
    	if (!is_file($file) || !is_readable($file))
    		return null;
    	$lines = file($file);
    	if ($lines === false)
    		return null;
    	foreach ($lines as $line) {
    		if (preg_match('/^DUMP_TMP_D\s.+\w $/', $line, $match_setting)) {
    			$log->warning('The DUMP_TMP_D variable in /etc/psa/psa.conf contains odd characters. This can cause backup tasks to fail on this server. Please check http://kb.odin.com/113474 for details.');
    			$log->resultWarning();
    			return;
    		}
    	}
    	$log->resultOk();
    }

    //:INFO: Wrong order of loading Zend extensions ionCube declaraion in php.ini can cause to Apache fail http://kb.odin.com/en/1520
    function _checkZendExtensionsLoadingOrder()
    {
    	$log = Log::getInstance("Checking for the Zend extension declaraion in php.ini");

    	$phpini = Util::getPhpIni();
    	if ($phpini) {
    		foreach ($phpini as $line) {
    			if (preg_match('/^\s*zend_extension(_debug)?(_ts)?\s*=/i', $line, $match)) {
    				$log->warning('The server-wide php.ini file contains the declaration of the Zend extension. As a result, the Apache server may fail to start after the upgrade. Please check http://kb.odin.com/en/1520 for more details.');
    				$log->resultWarning();
    				return;
    			}
    		}
    	}
    	$log->resultOk();
    }

    //:INFO: JkWorkersFile directive in Apache configuration can lead to failed Apache configs re-generation during and after upgrade procedure http://kb.odin.com/en/113210
    function _checkJkWorkersFileDirective()
    {
    	$log = Log::getInstance("Checking for the JkWorkersFile directive in the Apache configuration");

        $httpd_include_d = Util::getSettingFromPsaConf('HTTPD_INCLUDE_D') . '/';
    	if (empty($httpd_include_d)) {
    		$warn = 'Unable to open /etc/psa/psa.conf';
    		$log->warning($warn);
    		$log->resultWarning();
    		return;
    	}

    	$handle = @opendir($httpd_include_d);
    	if (!$handle) {
    		$warn = 'Unable to open dir ' . $httpd_include_d;
    		$log->warning($warn);
    		$log->resultWarning();
    		return;
    		}

    	$configs = array();
    	while ( false !== ($file = readdir($handle)) ) {
    		if (preg_match('/^\./', $file) || preg_match('/zz0.+/i', $file) || is_dir($httpd_include_d . $file))
    		continue;
    		$configs[] = $file;
    	}

    	closedir($handle);
    	$warning = false;

    	foreach ($configs as $config) {
    		$config_content = Util::readfileToArray($httpd_include_d . '/' . $config);
    		if ($config_content) {
    			for ($i=0; $i<=count($config_content)-1; $i++) {
    				if (preg_match('/^(\s+)?JkWorkersFile.+/', $config_content[$i], $match)) {
   						$log->warning('The Apache configuration file "' . $httpd_include_d . $config . '" contains the "' . $match[0] . '" directive.' );
    					$warning = true;
    				}
    			}
    		}
    	}

    	if ($warning) {
    		$log->warning('The JkWorkersFile directive may cause problems during the Apache reconfiguration after the upgrade. Please check http://kb.odin.com/en/113210 for more details.');
    		$log->resultWarning();
    		return;
    	}
    	$log->resultOk();
    }

    //:INFO: Wrong GUI behavior if forwarding URL hasn't "http://" after upgrade to Plesk version above 10.4.4 http://kb.odin.com/en/113359
    function _checkForwardingURL()
    {
    	$log = Log::getInstance("Checking domain URLs");

    	$mysql = PleskDb::getInstance();
    	$sql = "SELECT htype, redirect FROM domains, forwarding WHERE domains.id=forwarding.dom_id AND forwarding.redirect NOT LIKE 'https://%' AND forwarding.redirect NOT LIKE 'http://%'";
    	$domains_with_wrong_url = $mysql->fetchAll($sql);

    	if (count($domains_with_wrong_url)>0) {
    		$log->warning('There are domains registered in Panel which URL does not have the http:// prefix. Such domains will not be shown on the Domains page. Check http://kb.odin.com/en/113359 for more details.');
    		$log->resultWarning();
    		return;
    	}
    	$log->resultOk();
    }

    //:INFO: Customized DNS records with host equal host of existing subdomain will lost after upgrade to Plesk version above 10.4.4
    function _checkCustomDNSrecordsEqualToExistedSubdomains()
    {
    	$log = Log::getInstance("Checking DNS records of subdomains");

    	$mysql = PleskDb::getInstance();

    	if (PleskVersion::is10_2_or_above()) {
    		$sql = "SELECT DISTINCT dns_recs.dns_zone_id, dns_recs.type, host, val, opt FROM dns_recs, subdomains, domains WHERE host = concat(subdomains.name,'.', domains.name,'.') AND dns_recs.dns_zone_id IN ( SELECT dns_recs.dns_zone_id FROM dns_recs, subdomains, domains, hosting, IP_Addresses, DomainServices, IpAddressesCollections WHERE host = concat(subdomains.name,'.', domains.name,'.') AND subdomains.dom_id = domains.id AND domains.id = DomainServices.dom_id AND DomainServices.type = 'web' AND DomainServices.ipCollectionId = IpAddressesCollections.ipCollectionId AND IpAddressesCollections.ipAddressId = IP_Addresses.id AND IP_Addresses.ip_address <> dns_recs.val)";
    	} else {
    		$sql = "SELECT DISTINCT dns_recs.dns_zone_id, dns_recs.type, host, val, opt FROM dns_recs, subdomains, domains WHERE host = concat(subdomains.name,'.', domains.name,'.') AND dns_recs.dns_zone_id IN ( SELECT dns_recs.dns_zone_id FROM dns_recs, subdomains, domains, hosting, IP_Addresses WHERE host = concat(subdomains.name,'.', domains.name,'.') AND subdomains.dom_id = domains.id AND domains.id = hosting.dom_id AND hosting.ip_address_id = IP_Addresses.id AND IP_Addresses.ip_address <> dns_recs.val)";
    	}

    	if (Util::isWindows()) {
    		$dbprovider = Util::regPleskQuery('PLESK_DATABASE_PROVIDER_NAME');
    		if ($dbprovider <> 'MySQL') {
    			$sql = "SELECT DISTINCT dns_recs.dns_zone_id, dns_recs.type, host, val, opt FROM dns_recs, subdomains, domains WHERE host = (subdomains.name + '.' + domains.name + '.') AND dns_recs.dns_zone_id IN ( SELECT dns_recs.dns_zone_id FROM dns_recs, subdomains, domains, hosting, IP_Addresses WHERE host = (subdomains.name + '.' + domains.name + '.') AND subdomains.dom_id = domains.id AND domains.id = hosting.dom_id AND hosting.ip_address_id = IP_Addresses.id AND IP_Addresses.ip_address <> dns_recs.val)";
    		}
    	}
    	$problem_dns_records = $mysql->fetchAll($sql);

    	if (count($problem_dns_records)>0) {
    		$log->warning('There is a number of DNS records for the subdomains that you manually added to domain DNS zones. If you upgrade to Panel 10.4.4, these records will be lost. Check http://kb.odin.com/en/113310 for more details.');
    		$log->resultWarning();
    		return;
    	}
    	$log->resultOk();
    }

    //:INFO: Check windows authentication for PleskControlPanel web site http://kb.odin.com/en/113253
    function _checkWindowsAuthForPleskControlPanel()
    {
    	$log = Log::getInstance('Checking the authentication settings of the PleskControlPanel website in IIS');

    	$PleskControlPanel = 'PleskControlPanel';
    	$cmd = 'wmic.exe /namespace:\\\\root\\MicrosoftIISv2 path IIsWebServerSetting where "ServerComment = \'' . $PleskControlPanel . '\'" get name /VALUE';
    	$output = Util::exec($cmd, $code);
    	if (preg_match_all('/Name=(.+)/', $output, $siteName)) {
    		$cmd = 'wmic.exe /namespace:\\\\root\\MicrosoftIISv2 path IIsWebVirtualDirSetting where "Name = \'' . $siteName[1][0] . '/ROOT\'" get AuthNTLM /VALUE';
    		$output = Util::exec($cmd, $code);
    		if (preg_match_all('/AuthNTLM=FALSE/', $output, $matches)) {
    			$log->warning('Windows authentication for the PleskControlPanel website in IIS is disabled. Check http://kb.odin.com/en/113253 for more details.');
    			$log->resultWarning();
    			return;
    		}

    	}
    	$log->resultOk();
    }

    //:INFO: Plesk 11.6.3 version does not support ClamAV Antivirus
    function _unsupportedClamAvWarningOnUpgradeTo12_0x()
    {
        $log = Log::getInstance('Obtaining information about the installed antivirus');
        $antivirus = PleskComponent::CurrentWinAntivirus();
        if ( $antivirus == 'clamav') {
            $warn = 'Starting from Parallels Plesk 12.0, the ClamAV antivirus is no longer supported.';
            $log->warning($warn);
            $log->resultWarning();

            return;
        }
        $log->resultOk();
    }

    //:INFO: Plesk 11.6.4 version does not support mylittleadmin 2000
    function _unsupportedMla2000WarningOnUpgradeTo12_0x()
    {
        $log = Log::getInstance('Obtaining information about the installed administration tool for SQL Server');
        $mssqladmin = PleskComponent::CurrentWinMssqlWebAdmin();
        if ( $mssqladmin == 'mylittleadmin') {
            $warn = 'Starting from Parallels Plesk 12.0, MyLittleAdmin 2000 is no longer supported.';
            $log->warning($warn);
            $log->resultWarning();

            return;
        }
        $log->resultOk();
    }

    //:INFO: Plesk 11.6.3 version does not support Urchin web statistics
    function _unsupportedUrchinWarningOnUpgradeTo12_0x()
    {
        $log = Log::getInstance('Detecting if Urchin is installed');
        $urchin = Util::regQuery('\PLESK\PSA Config\Config\Packages\stats.urchin', '', true);
        if ($urchin == 'urchin'){
            $log->info('Urchin is installed');

            $pleskDb = PleskDb::getInstance();
            $sql = "SELECT * FROM tmpldata WHERE element = 'webstat' AND value = 'urchin'";
            $tmplrow = $pleskDb->fetchRow($sql);
            $sql = "SELECT * FROM hosting WHERE webstat = 'urchin'";
            $hostrow = $pleskDb->fetchRow($sql);

            if (!empty($tmplrow) || !empty($hostrow)){
                $log->info('Urchin is used');

                $warn = 'Starting from Parallels Plesk 12.0, Urchin is no longer supported.';
                $log->warning($warn);
                $log->resultWarning();
                return;
            } else
                $log->info('Urchin is not used');

        } else
            $log->info('Urchin is not installed');
        $log->resultOk();
    }

    function _unsupportedMSSQLForPleskInternalDbOnUpgradeTo12_1()
    {
        $log = Log::getInstance('Detecting if Microsoft SQL Server is used for the Plesk\'s internal database...');
        $pleskDbType = Util::getPleskDbType();
        if ($pleskDbType == 'mssql') {
            $msg = "The Plesk's internal database has been running on Microsoft SQL Server. Please switch to MySQL server. For instructions, see http://download1.parallels.com/Plesk/Doc/en-US/online/plesk-win-advanced-administration-guide/index.htm?fileName=50852.htm".
                "\nOtherwise, the upgrade will fail.";
            $log->emergency($msg);
            $log->resultError();
        }
        else {
            $log->resultOk();
        }
    }

    //:INFO: Notification about after upgrade all subdomains will have own DNS zone http://kb.odin.com/en/112966
    function _notificationSubDomainsHaveOwnDNSZoneSince104()
    {
    	$log = Log::getInstance('Checking for subdomains...');

    	$mysql = PleskDb::getInstance();
    	$sql = "select val from misc where param='subdomain_own_zones'";
    	$subdomain_own_zones = $mysql->fetchOne($sql);

    	if ($subdomain_own_zones == "true") {
    		$log->warning('Since Panel 10.4, all subdomains have their own DNS zone. Check http://kb.odin.com/en/112966 for more details.');
    		$log->resultWarning();
    		return;
    	}
    	$log->resultOk();
    }

    //:INFO: Customized CNAME records in server's DNS template could lead to misconfiguration of BIND http://kb.odin.com/en/113118
    function _checkCustomizedCnameRecordsInDnsTemplate()
    {
    	$log = Log::getInstance("Checking for CNAME records added to the initial Panel DNS template");

    	$mysql = PleskDb::getInstance();
    	$sql = "select * from dns_recs_t where type='CNAME' and host in ('<domain>.','ns.<domain>.','mail.<domain>.','ipv4.<domain>.','ipv6.<domain>.','webmail.<domain>.')";
    	$records = $mysql->fetchOne($sql);
    	if (!empty($records)) {
    		$log->warning("There are CNAME records that were added to the initial Panel DNS template. These records may cause incorrect BIND operation after upgrade. Please check http://kb.odin.com/en/113118 for more details.");
    		$log->resultWarning();
    		return;
    	}
    	$log->resultOk();
    }

    //:INFO: Upgrade of Customer & Business Manager failed in case of 127.0.0.1 is restricted for administrative access http://kb.odin.com/en/113096
    function _checkPanelAccessForLocalhost()
    {
    	$log = Log::getInstance('Checking for restriction policy');

    	$mysql = PleskDb::getInstance();
    	$sql = "select val from cl_param where param='ppb-url'";
    	$url = $mysql->fetchOne($sql);
    	if (!empty($url)) {
    		$sql = "select val from misc where param='access_policy'";
    		$policy = $mysql->fetchOne($sql);
    		$sql = "select netaddr from misc m,cp_access c where m.param='access_policy' and m.val='allow' and c.netaddr='127.0.0.1' and c.type='allow';";
    		$allow = $mysql->fetchOne($sql);
    		$sql = "select netaddr from misc m,cp_access c where m.param='access_policy' and m.val='deny' and c.netaddr='127.0.0.1' and c.type='deny';";
    		$deny = $mysql->fetchOne($sql);

    		if (!empty($allow)
    			|| (empty($deny) && $policy == 'deny')) {
    			$log->warning('The IP address 127.0.0.1 is restricted for administrative access. Upgrade of the Customer & Business Manager component will be impossible. Please check http://kb.odin.com/en/113096 for more details.');
    			$log->resultWarning();
    			return;
    		}
    	}
    	$log->resultOk();
    }

    //:INFO: Checking that limits are not exceeded in /proc/user_beancounters
    function _checkUserBeancounters()
    {
    	$log = Log::getInstance("Checking that limits are not exceeded in /proc/user_beancounters");

    	$warning = false;
    	$user_beancounters = Util::readfileToArray('/proc/user_beancounters');
    	if ($user_beancounters) {
    		for ($i=2; $i<=count($user_beancounters)-1; $i++) {
    			if (preg_match('/\d{1,}$/', $user_beancounters[$i], $match)
    			&& $match[0]>0) {
    				if (preg_match('/^.+?:?.+?\b(\w+)\b/', $user_beancounters[$i], $limit_name)) {
    					$log->warning('Virtuozzo Container limit "' . trim($limit_name[1]) . '" was exceeded ' . $match[0] . ' times.');
    				}
    				$warning = true;
    			}
    		}
    	}

    	if ($warning) {
    		$log->warning('Limits set by Parallels Virtuozzo Container are exceeded. Please, check http://kb.odin.com/en/112522 for more details.');
    		$log->resultWarning();
    		return;
    	}

    	$log->resultOk();
    }

    //:INFO: Checking for available free memory for the upgrade procedure http://kb.odin.com/en/112522
    function _checkFreeMemory()
    {
    	$log = Log::getInstance('Checking for available free memory for the upgrade procedure');

    	$freeMem = Util::GetFreeSystemMemory();
    	if (!empty($freeMem)
    		&& $freeMem < 200000) {
    		$log->warning('Not enough memory to perform the upgrade: You should have at least 200 megabytes free. The current amount of free memory is: ' . $freeMem . ' Kb');
    		$log->resultWarning();
    	}
    	$log->resultOk();
    }

    //:INFO: Check for Customer and Business Manager property in license key  http://kb.odin.com/en/111143
    function _checkCBMlicense()
    {
    	$log = Log::getInstance('Checking if the license key includes support for Customer and Business Manager');

    	$mysql = PleskDb::getInstance();
    	$sql = "select val from cl_param where param='ppb-url'";
    	$url = $mysql->fetchOne($sql);
    	$warning = false;
    	if (!empty($url)) {
    		if (Util::isLinux()) {
    			$license_folder = '/etc/sw/keys/keys/';
    		} else {
    			$license_folder = Util::getPleskRootPath() . 'admin\\repository\\keys\\';
    		}
    		$license_files = scandir($license_folder);
    		for ($i = 2; $i <= count($license_files) - 1; $i++) {
    			$file = file_get_contents($license_folder . $license_files[$i]);

    			if (preg_match('/modernbill.+\>(.+)\<.+modernbill/', $file, $accounts)) {
					if ($accounts[1] > 0) {
						$log->resultOk();
						return;
					}
    			}
    		}

    		$log->warning('If you had not purchased the Customer and Business Manager License you can not use it after the upgrade. Check the article http://kb.odin.com/en/111143 for more details.');
    		$log->resultWarning();
    	}


    }

    //:INFO: Check the availability of Plesk Panel TCP ports
    function _checkPleskTCPPorts()
    {
    	$log = Log::getInstance('Checking the availability of Plesk Panel TCP ports');

    	$plesk_ports = array('8880' => 'Plesk Panel non-secure HTTP port', '8443' => 'Plesk Panel secure HTTPS port');

    	$mysql = PleskDb::getInstance();
    	$sql = "select ip_address from IP_Addresses";
    	$ip_addresses = $mysql->fetchAll($sql);
    	$warning = false;
    	if (count($ip_addresses)>0) {
    		if (Util::isLinux()) {
    			$ipv4 = Util::getIPv4ListOnLinux();
    			$ipv6 = Util::getIPv6ListOnLinux();
    			if ($ipv6) {
    				$ipsInSystem = array_merge($ipv4, $ipv6);
    			} else {
    				$ipsInSystem = $ipv4;
    			}
    		} else {
    			$ipsInSystem = Util::getIPListOnWindows();
    		}
    		foreach ($ip_addresses as $ip) {
    			foreach ($plesk_ports as $port => $description) {
    				if (PleskValidator::validateIPv4($ip['ip_address']) && in_array($ip['ip_address'], $ipsInSystem)) {
    					$fp = @fsockopen($ip['ip_address'], $port, $errno, $errstr, 1);
    				} elseif (PleskValidator::validateIPv6($ip['ip_address']) && in_array($ip['ip_address'], $ipsInSystem)) {
    					$fp = @fsockopen('[' . $ip['ip_address'] . ']', $port, $errno, $errstr, 1);
    				} else {
    					$log->warning('IP address registered in Plesk is invalid or broken: ' . $ip['ip_address']);
    					$log->resultWarning();
    					return;
    				}
    				if (!$fp) {
    					// $errno 110 means "timed out", 111 means "refused"
    					$log->info('Unable to connect to IP address ' . $ip['ip_address'] . ' on ' . $description . ' ' . $port . ': ' . $errstr);
    					$warning = true;
    				}
    			}
    		}
    	}
    	if ($warning) {
    		$log->warning('Unable to connect to some Plesk ports. Please see ' . LOG_PATH . ' for details. Find the full list of the required open ports at http://kb.odin.com/en/391 ');
    		$log->resultWarning();
    		return;
    	}
    	$log->resultOk();
    }

	function _getPAMServiceIncludes($serviceFile)
	{
		// Get array of PAM services that are included from a given PAM configuration file.
		$lines = file($serviceFile);
		$includes = array();

		foreach ($lines as $line) {
			// Note: we do not support here line continuations and syntax variants for old unsupported systems.
			$line = trim( preg_replace('/#.*$/', '', $line) );
			if (empty($line))
				continue;

			// See PAM installation script source for info on possible syntax variants.
			$tokens = preg_split('/\s+/', $line);
			$ref = null;
			if ($tokens[0] == '@include') {
				$ref = $tokens[1];
			} elseif ($tokens[1] == 'include' || $tokens[1] == 'substack') {
				$ref = $tokens[2];
			}

			if (!empty($ref)) {
				$includes[] = $ref;
			}
		}

		return $includes;
	}

	//:INFO: Check that non-existent PAM services are not referenced by other services, i.e. that PAM configuration array is consistent http://kb.odin.com/en/112944
	function _checkPAMConfigsInclusionConsistency()
	{
		$log = Log::getInstance('Checking PAM configuration array consistency');

		$pamDir = "/etc/pam.d/";
		$handle = @opendir($pamDir);
		if (!$handle) {
			$warn = 'Unable to open the PAM configuration directory "' . $pamDir . '". Check http://kb.odin.com/en/112944 for more details.';
			$log->warning($warn);
			$log->resultWarning();
			return;
		}

		$services = array();
		while ( false !== ($file = readdir($handle)) ) {
			if (preg_match('/^\./', $file) || preg_match('/readme/i', $file) || is_dir($pamDir . $file))
				continue;
			$services[] = $file;
		}

		closedir($handle);

		$allIncludes = array();
		foreach ($services as $service) {
			$includes = $this->_getPamServiceIncludes($pamDir . $service);
			$allIncludes = array_unique(array_merge($allIncludes, $includes));
		}

		$missingIncludes = array_diff($allIncludes, $services);

		if (!empty($missingIncludes)) {
			$warn  = 'The PAM configuration is in inconsistent state. ';
			$warn .= 'If you proceed with the installation, the required PAM modules will not be installed. This will cause problems during the authentication. ';
			$warn .= 'Some PAM services reference the following nonexistent services: ' . implode(', ', $missingIncludes) . '. ';
			$warn .= 'Check http://kb.odin.com/en/112944 for more details.';

			$log->warning($warn);
			$log->resultWarning();
			return;
		}

		$log->resultOk();
	}

    //:INFO: Plesk user "root" for MySQL database servers have not access to phpMyAdmin http://kb.odin.com/en/112779
    function _checkMySQLDatabaseUserRoot()
    {
    	$log = Log::getInstance('Checking existence of Plesk user "root" for MySQL database servers');

    	$psaroot = Util::getSettingFromPsaConf('PRODUCT_ROOT_D');
    	$phpMyAdminConfFile = $psaroot . '/admin/htdocs/domains/databases/phpMyAdmin/libraries/config.default.php';
    	if (file_exists($phpMyAdminConfFile)) {
    		$phpMyAdminConfFileContent = file_get_contents($phpMyAdminConfFile);
    		if (!preg_match("/\[\'AllowRoot\'\]\s*=\s*true\s*\;/", $phpMyAdminConfFileContent)) {
    			$mysql = PleskDb::getInstance();
    			$sql = "select login, data_bases.name as db_name, displayName as domain_name from db_users, data_bases, domains where db_users.db_id = data_bases.id and data_bases.dom_id = domains.id and data_bases.type = 'mysql' and login = 'root'";
    			$dbusers = $mysql->fetchAll($sql);

    			foreach ($dbusers as $user) {
    				$log->warning('The database user "' . $user['login'] . '"  (database "' . $user['db_name'] . '" at "' . $user['domain_name'] . '") has no access to phpMyAdmin. Please check http://kb.odin.com/en/112779 for more details.');
    				$log->resultWarning();
    				return;
    			}
    		}
    	}

    	$log->resultOk();
    }

    //:INFO: After upgrade Plesk change permissions on folder of Collaboration Data Objects (CDO) for NTS (CDONTS) to default, http://kb.odin.com/en/111194
    function _checkCDONTSmailrootFolder()
    {
    	$log = Log::getInstance('Checking for CDONTS mailroot folder');

    	$mailroot = Util::getSystemDisk() . 'inetpub\mailroot\pickup';

    	if (is_dir($mailroot)) {
    		$log->warning('After upgrade you have to add write pemissions to psacln group on folder ' . $mailroot . '. Please, check http://kb.odin.com/en/111194 for more details.');
    		$log->resultWarning();
    		return;
    	}
    	$log->resultOk();
    }

    //:INFO: Checking for conflicting of SSO start-up scripts http://kb.odin.com/en/112666
    function _checkSsoStartScriptsPriority()
    {
    	$log = Log::getInstance('Checking for SSO start-up script priority');

    	$sso_script = '/etc/sw-cp-server/applications.d/00-sso-cpserver.conf';
    	$sso_folder = '/etc/sso';

    	if (!file_exists($sso_script)
    	&& is_dir($sso_folder)) {
    		$log->warning('SSO start-up script has wrong execution priority. Please, check http://kb.odin.com/en/112666 for more details.');
    		$log->resultWarning();
    		return;
    	}
    	$log->resultOk();
    }

    //:INFO: Check iisfcgi.dll file version http://kb.odin.com/en/112606
    function _checkIisFcgiDllVersion()
    {
    	$log = Log::getInstance("Checking the iisfcgi.dll file version");

    	$windir = Util::getSystemRoot();
		$iisfcgi = $windir . '\system32\inetsrv\iisfcgi.dll';
		if (file_exists($iisfcgi)) {
		  	$version = Util::getFileVersion($iisfcgi);
    		if (version_compare($version, '7.5.0', '>')
    			&& version_compare($version, '7.5.7600.16632', '<')) {
    			$log->warning('File iisfcgi.dll version ' . $version . ' is outdated. Please, check article http://kb.odin.com/en/112606 for details');
    			return;
    		}
		}
		$log->resultOk();
    }

    //:INFO: Users with same e-mails as e-mail accounts will have problems with changing personal contact information http://kb.odin.com/en/112032
    function _checkUserHasSameEmailAsEmailAccount()
    {
    	$log = Log::getInstance("Checking for users with same e-mail address as e-mail account");

    	$mysql = PleskDb::getInstance();
    	$sql = "select login, email from clients where email in (select concat(m.mail_name, '@', d.displayName) from domains d, mail m, Permissions p where m.perm_id=p.id and (p.permission='cp_access' and value='true'))";
    	if (Util::isWindows()) {
    		$dbprovider = Util::regPleskQuery('PLESK_DATABASE_PROVIDER_NAME');
    		if ($dbprovider <> 'MySQL') {
    			$sql = "select login, email from clients where email in (select (m.mail_name + '@' + d.displayName) from domains d, mail m, Permissions p where m.perm_id=p.id and (p.permission='cp_access' and value='true'))";
    		}
    	}
    	if (PleskVersion::is10x()) {
    		$sql = "select count(login) users, email from smb_users where email in (select email from smb_users group by email having count(email)>1) and email != '' group by email";
    	}

    	$problem_clients = $mysql->fetchAll($sql);

    	if (PleskVersion::is8x()
    		|| PleskVersion::is9x()) {
    		$sql = "select d.name domain_name, c.email domain_admin_email from domains d, dom_level_usrs dl, Cards c where c.id=dl.card_id and dl.dom_id=d.id and c.email in (select concat(m.mail_name, '@', d.displayName) from domains d, mail m, Permissions p where m.perm_id=p.id and (p.permission='cp_access' and value='true'))";
    		if (Util::isWindows()) {
    			$dbprovider = Util::regPleskQuery('PLESK_DATABASE_PROVIDER_NAME');
    			if ($dbprovider <> 'MySQL') {
    				$sql = "select d.name as domain_name, c.email as domain_admin_email from domains d, dom_level_usrs dl, Cards c where c.id=dl.card_id and dl.dom_id=d.id and c.email in (select (m.mail_name + '@' + d.displayName) from domains d, mail m, Permissions p where d.id=m.dom_id and m.perm_id=p.id and (p.permission='cp_access' and value='true'))";
    			}
    		}
    	}
    	$problem_domain_admins = $mysql->fetchAll($sql);

    	if (count($problem_clients)>0
    		|| count($problem_domain_admins)>0) {
    		foreach ($problem_clients as $client) {
    			if (PleskVersion::is10x()) {
    				$info = 'There are ' . $client['users'] . ' users with the same contact e-mail address ' . $client['email'];
    			} else {
    				$info = 'User ' . $client['login'] . ' has contact mail address as e-mail account ' . $client['email'];
    			}
    			$log->info($info);
    		}
    		foreach ($problem_domain_admins as $domain_admin) {
    			$info = 'Domain administrator of domain ' . $domain_admin['domain_name'] . ' has contact mail address as e-mail account ' . $domain_admin['domain_admin_email'];
    			$log->info($info);
    		}
    		if (PleskVersion::is10x()) {
    			$log->warning('There are a number of Panel users that have the same contact email. Please see the ' . LOG_PATH . ' for details.  You will not be able to change personal information (including passwords) of these users. Learn more at http://kb.odin.com/en/112032.');
    		} else {
    			$log->warning('There are some users found with email matches mailboxes with permission to access Customer Panel. See the ' . LOG_PATH . ' for details.  If a client\'s or domain administrator\'s e-mail address (in the profile) matches a mailbox in Plesk and the mailbox has the permission to access Customer Panel, the upgrade procedure will create two auxiliarily user accounts (with the same e-mail) for such customers and Panel will not allow to change personal information (including passwords) for them. Please, check http://kb.odin.com/en/112032 for more details.');
    		}

    		$log->resultWarning();
    		return;
    	}

    	$log->resultOk();
    }

    //:INFO: Checking for main IP address http://kb.odin.com/en/112417
    function _checkMainIP()
    {
    	$log = Log::getInstance("Checking for main IP address");

    	$mysql = PleskDb::getInstance();
    	$sql = 'select * from IP_Addresses';
    	$ips = $mysql->fetchAll($sql);
    	$mainexists = false;
    	foreach ($ips as $ip) {
    		if (isset($ip['main'])) {
    			if ($ip['main'] == 'true') {
    				$mainexists = true;
    			}
    		} else {
    			$log->info('No field "main" in table IP_Addresses.');
    			$log->resultOk();
    			return;
    		}
    	}

    	if (!$mainexists) {
    		$warn = 'Unable to find "main" IP address in psa database. Please, check http://kb.odin.com/en/112417 for more details.';
    		$log->warning($warn);
    		$log->resultWarning();
    		return;
    	}
    	$log->resultOk();
    }

    //:INFO: Checking for hostname in Customer & Business Manager relay URL http://kb.odin.com/en/111500
    function _checkCBMrelayURL()
    {
    	$log = Log::getInstance("Checking for hostname in Customer & Business Manager relay URL");

    	$mysql = PleskDb::getInstance();
    	$sql = "select val from cl_param where param='ppb-url'";
    	$url = $mysql->fetchOne($sql);
    	if (preg_match("/\/\/(.*):/i", $url, $result)) {
    		if (!PleskValidator::isValidIp($result[1])) {
    			$ip = Util::resolveHostname($result[1]);
    			if (!Util::isFQDN($result[1])
    			|| !PleskValidator::isValidIp($ip)) {
    				$warn = 'If you see the 404 error when trying to access Customer & Business Manager, please see http://kb.odin.com/en/111500 for the soultion.';
    				$log->warning($warn);
    				$log->resultWarning();
    				return;
    			}
    		}
    	}
    	$log->resultOk();
    }

    //:INFO:#66278,70525 Checking SQL mode of default client's MySQL server http://kb.odin.com/en/112453
    function _checkDefaultMySQLServerMode()
    {
    	$log = Log::getInstance("Checking SQL mode of default client's MySQL server");

    	$credentials = Util::getDefaultClientMySQLServerCredentials();
    	if (!empty($credentials)) {
    		$mysql = new DbClientMysql('localhost', $credentials['admin_login'], $credentials['admin_password'] , 'mysql', 3306);
    		if (!$mysql->hasErrors()) {
    			$sql = 'SELECT @@sql_mode';
    			$sqlmode = $mysql->fetchOne($sql);
    			if (preg_match("/STRICT_/i", $sqlmode, $match)) {
    				$warn = 'Please, switch off strict mode for MySQL server. Read carefully article http://kb.odin.com/en/112453 for details.';
    				$log->warning($warn);
    				$log->resultWarning();
    				return;
    			}
    		}
    	}

    	$log->resultOk();
    }

    //:INFO: If user has the same address as the admin it should be changed to another http://kb.odin.com/en/111985
    function _checkUserHasSameEmailAsAdmin()
    {
    	$log = Log::getInstance('Checking for users with the same e-mail address as the administrator');

    	$adminEmail = Plesk10BusinessModel::_getAdminEmail();
    	if (!empty($adminEmail)) {
    		$db = PleskDb::getInstance();
    		if (PleskVersion::is10x_or_above()) {
    			$sql = "SELECT login, email FROM smb_users WHERE login<>'admin' and email='" . $adminEmail . "'";
    			$clients = $db->fetchAll($sql);
    		} else {
    			$sql = "SELECT login, email FROM clients WHERE login<>'admin' and email='" . $adminEmail . "'";
    			$clients = $db->fetchAll($sql);
    		}
    		if (!empty($clients)) {
    			foreach ($clients as $client) {
    				$log->info('The customer with the username ' . $client['login'] . ' has the same e-mail address as the Panel administrator: ' .  $client["email"]);
    			}
    			$log->warning('Some customers have e-mail addresses coinciding with the Panel administrator\'s e-mail address. Please see the ' . LOG_PATH . ' and check http://kb.odin.com/en/111985 for details.');
    			$log->resultWarning();
    			return;
    		}
    	}
    	$log->resultOk();
    }

    //:INFO: Check that .net framework installed properly http://kb.odin.com/en/111448
    function _checkDotNetFrameworkIssue()
    {
    	$log = Log::getInstance('Checking that .NET framework installed properly');

    	$pleskCpProvider = Util::regPleskQuery('PLESKCP_PROVIDER_NAME', true);
    	if ($pleskCpProvider == 'iis') {
    		$cmd = '"' . Util::regPleskQuery('PRODUCT_ROOT_D', true) . 'admin\bin\websrvmng" --list-wdirs --vhost-name=pleskcontrolpanel';
    		$output = Util::exec($cmd, $code);
    		if (!preg_match("/wdirs/i", trim($output), $matches)) {
    			$log->warning('There is a problem with .NET framework.  Please, check http://kb.odin.com/en/111448 for details.');
    			$log->resultWarning();
    			return;
    		}
    	}
    	$log->resultOk();

    }

    //:INFO: Check for custom php.ini on domains http://kb.odin.com/en/111697
    function _checkCustomPhpIniOnDomains()
    {
    	$log = Log::getInstance('Checking for custom php.ini on domains');

    	$domains = Plesk10BusinessModel::_getDomainsByHostingType('vrt_hst');
    	if (empty($domains)) {
    		$log->resultOk();
    		return;
    	}
    	$vhost = Util::getSettingFromPsaConf('HTTPD_VHOSTS_D');
    	if (empty($vhost)) {
    		$warn = 'Unable to read /etc/psa/psa.conf';
    		$log->warning($warn);
    		$log->resultWarning();
    		return;
    	}
    	$flag = false;
    	foreach ($domains as $domain) {
    		$filename = $vhost . '/' . $domain['name'] . '/conf/php.ini';
    		if (file_exists($filename)) {
    			$warn = 'Custom php.ini is used for domain ' . $domain['name'] . '.';
    			$log->warning($warn);
    			$flag = true;
    		}
    	}

    	if ($flag) {
    		$warn = 'After upgrade, Panel will not apply changes to certain website-level PHP settings due to they are predefined in /var/www/vhosts/DOMAINNAME/conf/php.ini. Please check http://kb.odin.com/en/111697 for details.';
    		$log->warning($warn);
    		$log->resultWarning();
    		return;
    	}

    	$log->resultOk();
    }

    //:INFO: Checking existing table mysql.servers http://kb.odin.com/en/112290
    function _checkMysqlServersTable()
    {
    	$log = Log::getInstance('Checking table "servers" in database "mysql"');

    	$mySQLServerVersion = Util::getMySQLServerVersion();
    	if (version_compare($mySQLServerVersion, '5.1.0', '>=')) {
    		$credentials = Util::getDefaultClientMySQLServerCredentials();

    		if (!Util::isLinux() && preg_match('/AES-128-CBC/', $credentials['admin_password'])) {
    			$log->info('The administrator\'s password for the default MySQL server is encrypted.');
    			return;
    		}

    		$mysql = new DbClientMysql('localhost', $credentials['admin_login'], $credentials['admin_password'] , 'information_schema', 3306);
    		if (!$mysql->hasErrors()) {
    			$sql = 'SELECT * FROM information_schema.TABLES  WHERE TABLE_SCHEMA="mysql" and TABLE_NAME="servers"';
    			$servers = $mysql->fetchAll($sql);
    			if (empty($servers)) {
    				$warn = 'The table "servers" in the database "mysql" does not exist. Please check  http://kb.odin.com/en/112290 for details.';
    				$log->warning($warn);
    				$log->resultWarning();
    				return;
    			}
    		}
    	}
    	$log->resultOk();
    }

    //:INFO: Check that there is symbolic link /usr/local/psa on /opt/psa on Debian-like Oses http://kb.odin.com/en/112214
    function _checkSymLinkToOptPsa()
    {
    	$log = Log::getInstance('Checking symbolic link /usr/local/psa on /opt/psa');

    	$link = @realpath('/usr/local/psa/version');
    	if (!preg_match('/\/opt\/psa\/version/', $link, $macthes)) {
    		$warn = "The symbolic link /usr/local/psa does not exist or has wrong destination. Read article http://kb.odin.com/en/112214 to fix the issue.";
    		$log->warning($warn);
    		$log->resultWarning();
    		return;
    	}
    	$log->resultOk();
    }

    //:INFO: 81461 PPP-10378 Checking possibility to connect to client's MySQL server
    function _checkConnectToClientMySQL()
    {
    	$log = Log::getInstance('Checking connection to client MySQL server');

    	$credentials = Util::getDefaultClientMySQLServerCredentials();

    	if ($credentials == NULL) {
    		$installedMySQLserver55 = Util::regQuery('\MySQL AB\MySQL Server 5.5', '/v version', true);
    		$installedMySQLserver51 = Util::regQuery('\MySQL AB\MySQL Server 5.1', '/v version', true);
    		$installedMySQLserver50 = Util::regQuery('\MySQL AB\MySQL Server 5.0', '/v version', true);

    		if ($installedMySQLserver55
    		 	|| $installedMySQLserver51
    		 	|| $installedMySQLserver50
    		) {
    			$warn = 'Default MySQL server is not registered in Parallels Plesk Panel. If you use custom MySQL instances you should register one at least according to article http://kb.odin.com/en/111983.';
    			$log->warning($warn);
    			$log->resultWarning();
    			return;
    		}
    	}

    	if (preg_match('/AES-128-CBC/', $credentials['admin_password'])) {
    		$log->info('The administrator\'s password for the default MySQL server is encrypted.');
    		return;
    	}

    	$mysql = new DbClientMysql('localhost', $credentials['admin_login'], $credentials['admin_password'] , 'mysql', 3306);
    	if ($mysql->hasErrors()) {
            $warn = 'Unable to connect to the local default MySQL server. Please check  http://kb.odin.com/en/111983 for details.';
            $log->warning($warn);
            $log->resultWarning();
            return;
        }
    	$log->info('Connected sucessfully', true);
    	$result = $mysql->query('CREATE DATABASE IF NOT EXISTS pre_upgrade_checker_test_db');

    	if (!$result) {
            $warn = 'User has not enough privileges. Please check http://kb.odin.com/en/111983 for details.';
            $log->warning($warn);
            $log->resultWarning();
            return;
    	}
    	$result = $mysql->query('DROP DATABASE IF EXISTS pre_upgrade_checker_test_db');

    	if (!$result) {
            $warn = 'User has not enough privileges. Please check http://kb.odin.com/en/111983 for details.';
            $log->warning($warn);
            $log->resultWarning();
            return;
    	}
    	$log->resultOk();

    }

    //:INFO: Checking for unknown ISAPI filters and show warning http://kb.odin.com/en/111908
    function _unknownISAPIfilters()
    {
    	$log = Log::getInstance('Detecting installed ISAPI filters');

    	if (Util::isUnknownISAPIfilters()) {
    		$warn = 'Please read carefully article http://kb.odin.com/en/111908, for avoiding possible problems caused by unknown ISAPI filters.';
    		$log->warning($warn);
    		$log->resultWarning();

    		return;
    	}
    	$log->resultOk();
    }

    //:INFO: Warning about possible issues related to Microsoft Visual C++ Redistributable Packages ?http://kb.odin.com/en/111891
    function _checkMSVCR()
    {
    	$log = Log::getInstance('Microsoft Visual C++ Redistributable Packages');

		$warn = 'Please read carefully article http://kb.odin.com/en/111891, for avoiding possible problems caused by Microsoft Visual C++ Redistributable Packages.';
		$log->info($warn);

   		return;
    }

    function _diagnoseKavOnPlesk10x()
    {
        $log = Log::getInstance('Detecting if antivirus is Kaspersky');

        $pleskComponent = new PleskComponent();
        $isKavInstalled = $pleskComponent->isInstalledKav();

        $log->info('Kaspersky antivirus: ' . ($isKavInstalled ? ' installed' : ' not installed'));

        if (Util::isVz() && $isKavInstalled) {
            $warn = 'An old version of Kasperskiy antivirus is detected. ';
            $warn .= 'If you are upgrading to the Panel 10 using EZ templates, update the template of Kaspersky antivirus on hardware node to the latest version, and then upgrade the container.';
            $log->warning($warn);
            $log->resultWarning();

            return;
        }
        $log->resultOk();
    }

    function _diagnoseDependCycleOfModules()
    {
        //:INFO: Prevent potential problem with E: Couldn't configure pre-depend plesk-core for psa-firewall, probably a dependency cycle.
        $log = Log::getInstance('Detecting if Plesk modules are installed');

        if (Util::isVz()
            && PleskModule::isInstalledWatchdog()
            && PleskModule::isInstalledVpn()
            && PleskModule::isInstalledFileServer()
            && PleskModule::isInstalledFirewall()
        ) {
            $warn = 'Plesk modules "watchdog, fileserver, firewall, vpn" were installed on container. ';
            $warn .= 'If you are upgrading to the Panel 10 using EZ templates, remove the modules, and then upgrade the container.';
            $log->warning($warn);
            $log->resultWarning();

            return;
        }
        $log->resultOk();
    }
	
    function _checkForCryptPasswords()
    {
        //:INFO: Prevent potential problem with E: Couldn't configure pre-depend plesk-core for psa-firewall, probably a dependency cycle.
        $log = Log::getInstance('Detecting if encrypted passwords are used');

        $db = PleskDb::getInstance();
        $sql = "SELECT COUNT(*) AS cnt FROM accounts WHERE type='crypt' AND password not like '$%';";
        $r = $db->fetchAll($sql);

        if ($r[0]['cnt'] != '0')
        {
            $warn = 'There are ' . $r[0]['cnt'] . ' accounts with passwords encrypted using a deprecated algorithm. Please refer to http://kb.odin.com/en/112391 for the instructions about how to change the password type to plain.';

            $log->warning($warn);
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }
	
	//:INFO: PPPM-1405 http://kb.odin.com/en/112391
	function _checkForCryptPasswordsForMailAccounts()
    {
        $log = Log::getInstance('Detecting if old encrypted passwords are used');

        $db = PleskDb::getInstance();
		
		if (PleskVersion::is10x_or_above()) {
			$sql = "SELECT COUNT(*) AS cnt FROM accounts, mail where mail.account_id = accounts.id and type='crypt' AND mail.userId <> 0";
		} else {
			$sql = "SELECT COUNT(*) AS cnt FROM accounts, mail, Permissions where mail.account_id = accounts.id and type='crypt' and mail.perm_id = Permissions.id AND Permissions.permission = 'cp_access' AND Permissions.value = 'true'";
        }
		
		$r = $db->fetchAll($sql);

        if ($r[0]['cnt'] != '0')
        {
            $warn = 'There are ' . $r[0]['cnt'] . ' mail accounts with passwords encrypted using a deprecated algorithm. These users will be able to log in to mail server with PLAIN authentication mechanism but they will lose the ability to log in to Plesk. Please refer to http://kb.odin.com/120292 for details.';

            $log->warning($warn);
            $log->resultWarning();
            return;
        }
        $log->resultOk();
    }
	
    function _checkCustomVhostSkeletonStatisticsSubdir()
    {
        if (!(PleskVersion::is10_4_or_above() && version_compare(PleskVersion::getVersion(), '11.1.18', '<='))) {
            return;
        }

        // 'statistics' subdir in vhosts was removed starting from Plesk 11.1.18. It's customization will have no effect after upgrade.
        $log = Log::getInstance('Checking if the deprecated "statistics" subdirectory in virtual host templates can be removed');

        $unmodifiedSkelStatMd5sum = "3f5517860e8adfa4b05c9ea6268b38eb";
        $vhostsDir = Util::getSettingFromPsaConf('HTTPD_VHOSTS_D');
        $returnCode = 0;
        $currentSkelStatMd5sumList = Util::exec("find -L {$vhostsDir}/.skel/*/statistics -type f 2>/dev/null | xargs --no-run-if-empty md5sum | cut -d ' ' -f 1 | sort | uniq", $returnCode);

        if (empty($currentSkelStatMd5sumList)) {
            $log->info('The deprecated "statistics" subdirectory in virtual host template is already removed.');
            $log->resultOk();
        } elseif ($currentSkelStatMd5sumList == $unmodifiedSkelStatMd5sum) {
            $log->info('The "statistics" subdirectories of vhost templates do not contain custom content and will be safely removed during the upgrade.');
            $log->resultOk();
        } else {
            $warn = 'Some virtual host templates have customized content in the "statistics" subdirectories. In Plesk 11.5 and later, such customizations cannot be applied to domains because the "statistics" subdirectory is no longer used in the templates. ';
            $warn.= 'We recommend that you remove the "statistics" subdirectory from templates manually after the upgrade. ';
            $warn.= "You can find the \"statistics\" virtual hosts templates in {$vhostsDir}/.skel/*/statistics.";
            $log->warning($warn);
            $log->resultWarning();
        }
    }

    function _checkApsTablesInnoDB()
    {
        $log = Log::getInstance('Checking if apsc database tables have InnoDB engine');

        $db = PleskDb::getInstance();
        $apsDatabase = $db->fetchOne("select val from misc where param = 'aps_database'");
        $sql = "SELECT TABLE_NAME FROM information_schema.TABLES where TABLE_SCHEMA = '$apsDatabase' and ENGINE = 'MyISAM'";
        $myISAMTables = $db->fetchAll($sql);
        if (!empty($myISAMTables)) {
            $myISAMTablesList = implode(', ', array_map('reset', $myISAMTables));
            $warn = 'The are tables in apsc database with MyISAM engine: ' . $myISAMTablesList . '. It would be updated to InnoDB engine.';
            $log->warning($warn);
            $log->resultWarning();
            return;
    	}
    	$log->resultOk();
    }

    private function _checkDbUsersTableCollation()
    {
        $log = Log::getInstance('Checking if db_users table contains case insensitive duplicated login entries');

        $db = PleskDb::getInstance();
        $columns = $db->fetchAll("show full columns from db_users");
        $collation = null;
        $groupByColumns = array();
        foreach ($columns as $column) {
            if ('login' == $column['Field']) {
                $collation = $column['Collation'];
                $groupByColumns[] = "login COLLATE ascii_general_ci";
            }
            if (in_array($column['Field'], array('db_server_id', 'db_id'))) {
                $groupByColumns[] = $column['Field'];
            }
        }

        $duplicates = array();
        if ('ascii_bin' == $collation) {
            $uniqueKey = implode(',', $groupByColumns);
            $sql = "SELECT GROUP_CONCAT(login) AS logins FROM db_users GROUP BY {$uniqueKey} HAVING COUNT(*) > 1";
            foreach ($db->fetchAll($sql) as $duplicate) {
                $duplicates[] = $duplicate['logins'];
            }
        }
        if ($duplicates) {
            $msg = "There are duplicated entries in db_users table: " .
                 implode(" and ", $duplicates) . ". " .
                "Rename the database users to resolve the duplication.";
            $log->emergency($msg);
            return;
        }
        $log->resultOk();
    }
}

class PleskComponent
{
    function isInstalledKav()
    {
        return $this->_isInstalled('kav');
    }

    function _isInstalled($component)
    {
        //upgrade from 10.x version, use old database structure
		$sql = "SELECT * FROM ServiceNodeProperties WHERE name LIKE 'components.packages.%{$component}%'";

        $pleskDb = PleskDb::getInstance();
        $row = $pleskDb->fetchRow($sql);

        return (empty($row) ? false : true);
    }

    function CurrentWinFTPServer()
    {
    	if (Util::isWindows()) {
    		$currentFTPServer = Util::regQuery('\PLESK\PSA Config\Config\Packages\ftpserver', '/ve', true);
            $log = Log::getInstance();
    		$log->info('Current FTP server is: ' . $currentFTPServer);
    		return $currentFTPServer;
    	}
    }

    function CurrentWinAntivirus()
    {
        if (Util::isWindows()) {
            $currentAntivirus = Util::regQuery('\PLESK\PSA Config\Config\Packages\antivirus', '/ve', true);
            $log = Log::getInstance();
            $log->info('Current Antivirus is: ' . $currentAntivirus);
            return $currentAntivirus;
        }
    }

    function CurrentWinMssqlWebAdmin()
    {
        if (Util::isWindows()) {
            $currentMssqlAdmin = Util::regQuery('\PLESK\PSA Config\Config\Packages\sqladminmssql', '', true);
            $log = Log::getInstance();
            $log->info('Current MSSQL Web Admin is: ' . $currentMssqlAdmin);
            return $currentMssqlAdmin;
        }
    }

    function CurrentWinDNSServer()
    {
    	if (Util::isWindows()) {
    		$currentDNSServer = Util::regQuery('\PLESK\PSA Config\Config\Packages\dnsserver', '/ve', true);
            $log = Log::getInstance();
    		$log->info('Current DNS server is: ' . $currentDNSServer);
    		return $currentDNSServer;
    	}
    }

    function getPackageVersion($package_name)
    {
    	if (Util::isWindows()) {
			$cmd = '"' . Util::getPleskRootPath() . 'admin\bin\packagemng" ' . $package_name;
    	} else {
    		if (PleskVersion::is10_4_or_above()) {
    			$cmd = '/usr/local/psa/admin/bin/packagemng -l';
    		} else {
    			$cmd = '/usr/local/psa/admin/bin/packagemng ' . $package_name . ' 2>/dev/null';
    		}
    	}
    	/* packagemng <package name> - returns "<package name>:<package version>" on Windows all versions and Unix till Plesk 10.4 versions
    	 * since Plesk 10.4 on linux packagemng -l should be used to return list of all packages
    	 * if <package name> doesn't exists OR not installed on Windows output will be "<package name>:"
    	 * if <package name> doesn't installed on Linux output will be "<package name>:not_installed"
    	 * if <package name> doesn't exists on Linux output will be "packagemng: Package <package name> is not found in Components table"
    	 */
    	$output = Util::exec($cmd, $code);
    	if (preg_match('/' . $package_name .  '\:(.+)/', $output, $version)) {
    		if ($version[1] <> 'not_installed') {
    			return $version[1];
    		}
    	}
    	return null;
    }
}

class PleskModule
{
    function isInstalledWatchdog()
    {
        return PleskModule::_isInstalled('watchdog');
    }

    function isInstalledFileServer()
    {
        return PleskModule::_isInstalled('fileserver');
    }

    function isInstalledFirewall()
    {
        return PleskModule::_isInstalled('firewall');
    }

    function isInstalledVpn()
    {
        return PleskModule::_isInstalled('vpn');
    }

    function _isInstalled($module)
    {
        $sql = "SELECT * FROM Modules WHERE name = '{$module}'";

        $pleskDb = PleskDb::getInstance();
        $row = $pleskDb->fetchRow($sql);

        return (empty($row) ? false : true);
    }
}

class PleskInstallation
{
    function validate()
    {
        if (!$this->isInstalled()) {
            $log = Log::getInstance('Checking for Plesk installation');
            $log->step('Plesk installation is not found. You will have no problems with upgrade, go on and install '.PleskVersion::getLatestPleskVersionAsString().' (http://www.parallels.com/products/plesk/)');
            return;
        }
        $this->_detectVersion();
    }

    function isInstalled()
    {
        $rootPath = Util::getPleskRootPath();
        if (empty($rootPath) || !file_exists($rootPath)) {
            return false;
        }
        return true;
    }

    function _detectVersion()
    {
        $log = Log::getInstance('Installed Plesk version/build: ' . PleskVersion::getVersionAndBuild(), false);

        $currentVersion = PleskVersion::getVersion();
        if (version_compare($currentVersion, PLESK_VERSION, 'eq')) {
            $err = 'You have already installed the latest version ' . PleskVersion::getLatestPleskVersionAsString() . '. ';
            $err .= 'Tool must be launched prior to upgrade to ' . PleskVersion::getLatestPleskVersionAsString() . ' for the purpose of getting a report on potential problems with the upgrade.';
            // TODO either introduce an option to suppress fatal error here, or always exit with 0 here.
            //$log->fatal($err);
            $log->info($err);
            exit(0);
        }

        if (PleskVersion::is_below_10_0()) {
            $err = 'Upgrading to ' . PleskVersion::getLatestPleskVersionAsString() . ' from ' . PleskVersion::getCurrentPleskVersionAsString() . ' is not supported. ';
            $err .= 'Please upgrade to an earlier version (at least 10.4) before attempting to upgrade to ' . PleskVersion::getLatestPleskVersionAsString() . '.';
            // TODO drop all checks specific only to 8.x and 9.x
            $log->fatal($err);
        }

        if (!PleskVersion::is10x() && !PleskVersion::is11x() && !PleskVersion::is12x()) {
            $err = 'Unable to find Plesk 10.x, Plesk 11.x, or Plesk 12.x. ';
            $err .= 'Tool must be launched prior to upgrade to ' . PleskVersion::getLatestPleskVersionAsString() . ' for the purpose of getting a report on potential problems with the upgrade.';
            fatal($err);
        }
    }
}

class PleskVersion
{
    function is8x()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '8.0.0', '>=') && version_compare($version, '9.0.0', '<');
    }

    function is9x()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '9.0.0', '>=') && version_compare($version, '10.0.0', '<');
    }

    function is10x()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '10.0.0', '>=') && version_compare($version, '11.0.0', '<');
    }

    function is11x()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '11.0.0', '>=') && version_compare($version, '12.0.0', '<');
    }
	
	function is11x_or_above()
	{
		$version = PleskVersion::getVersion();
        return version_compare($version, '11.0.0', '>=');
	}

    function is12x()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '12.0.0', '>=') && version_compare($version, '13.0.0', '<');
    }

    function is_below_12_0()
    {
        // Historically it started as 11.6, so we check against it
        $version = PleskVersion::getVersion();
        return version_compare($version, '11.6.0', '<');
    }

    function is_below_12_1()
    {
        $version = PleskVersion::getVersion();
        return version_compare($version, '12.1.0', '<');
    }

    function is10_0()
    {
    	$version = PleskVersion::getVersion();
    	return version_compare($version, '10.0.0', '>=') && version_compare($version, '10.1.0', '<');
    }

    function is10x_or_above()
    {
    	$version = PleskVersion::getVersion();
    	return version_compare($version, '10.0.0', '>=');
    }

    function is_below_10_0()
    {
    	$version = PleskVersion::getVersion();
    	return version_compare($version, '10.0.0', '<');
    }

    function is10_1_or_below()
    {
    	$version = PleskVersion::getVersion();
    	return version_compare($version, '10.1.1', '<=');
    }

    function is10_2_or_above()
    {
    	$version = PleskVersion::getVersion();
    	return version_compare($version, '10.2.0', '>=');
    }

    function is10_3_or_above()
    {
    	$version = PleskVersion::getVersion();
    	return version_compare($version, '10.3.0', '>=');
    }

    function is10_4()
    {
    	$version = PleskVersion::getVersion();
    	return version_compare($version, '10.4.0', '>=') && version_compare($version, '10.5.0', '<');
    }

    function is10_4_or_above()
    {
    	$version = PleskVersion::getVersion();
    	return version_compare($version, '10.4.0', '>=');
    }

    function getVersion()
    {
        $version = PleskVersion::getVersionAndBuild();
        if (!preg_match('/([0-9]+[.][0-9]+[.][0-9]+)/', $version, $macthes)) {
            fatal("Incorrect Plesk version format. Current version: {$version}");
        }
        return $macthes[1];
    }

    function getVersionAndBuild()
    {
        $versionPath = Util::getPleskRootPath().'/version';
        if (!file_exists($versionPath)) {
            fatal("Plesk version file is not exists $versionPath");
        }
        $version = file_get_contents($versionPath);
        $version = trim($version);
        return $version;
    }

    function getLatestPleskVersionAsString()
    {
        return 'Plesk ' . PLESK_VERSION;
    }

    function getCurrentPleskVersionAsString()
    {
        return 'Plesk ' . PleskVersion::getVersion();
    }
}

class Log
{
    private $errors;
    private $warnings;
    private $emergency;
    private $logfile;
    private $step;
    private $step_header;

    public static function getInstance($step_msg = '', $step_number = true)
    {
        static $_instance = null;
        if (is_null($_instance)) {
            $_instance = new Log();
        }
        if ($step_msg)
            $_instance->step($step_msg, $step_number);
        return $_instance;
    }

    private function Log()
    {
        $this->log_init();
        @unlink($this->logfile);
    }

    private function log_init()
    {
        $this->step      = 0;
        $this->errors    = 0;
        $this->warnings  = 0;
        $this->emergency = 0;
        $this->logfile = LOG_PATH;
        $this->step_header = "Unknown step is running";
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    public function getEmergency()
    {
        return $this->emergency;
    }

    public function fatal($msg)
    {
        $this->errors++;

        $content = $this->get_log_string($msg, 'FATAL_ERROR');
        fwrite(STDERR, $content);
        $this->write($content);
    }

    public function error($msg)
    {
        $this->errors++;

        $content = $this->get_log_string($msg, 'ERROR');
        fwrite(STDERR, $content);
        $this->write($content);
    }

    public function warning($msg)
    {
        $this->warnings++;

        $content = $this->get_log_string($msg, 'WARNING');
        fwrite(STDERR, $content);
        $this->write($content);
    }

    public function emergency($msg)
    {
        $this->emergency++;

        $content = $this->get_log_string($msg, 'EMERGENCY');
        fwrite(STDERR, $content);
        $this->write($content);
    }

    public function step($msg, $useNumber=false)
    {
        $this->step_header = $msg;

        echo PHP_EOL;
        $this->write(PHP_EOL);

        if ($useNumber) {
            $msg = "STEP " . $this->step . ": {$msg}...";
            $this->step++;
        } else {
            $msg = "{$msg}...";
        }

        $this->info($msg);
    }

    public function resultOk()
    {
        $this->info('Result: OK');
    }

    public function resultWarning()
    {
        $this->info('Result: WARNING');
    }

    public function resultError()
    {
        $this->info('Result: ERROR');
    }

    public function info($msg)
    {
        $content = $this->get_log_string($msg, 'INFO');
        echo $content;
        $this->write($content);
    }

    public function debug($msg)
    {
        $this->write($this->get_log_string($msg, 'DEBUG'));
    }

    public function dumpStatistics()
    {
        $str = "Errors found: {$this->errors}; Warnings found: {$this->warnings}";
        echo PHP_EOL . $str . PHP_EOL . PHP_EOL;
    }

    private function get_log_string($msg, $type)
    {
        // TODO modern PHP (from 5.3) issues warning:
        //  PHP Warning:  date(): It is not safe to rely on the system's timezone settings. You are *required* to use the date.timezone setting
        //  or the date_default_timezone_set() function. In case you used any of those methods and you are still getting this warning, you most
        //  likely misspelled the timezone identifier. We selected 'America/New_York' for 'EDT/-4.0/DST' instead in
        //  panel_preupgrade_checker.php on line 1282
        if (getenv('VZ_UPGRADE_SCRIPT')) {
            switch ($type) {
                case 'FATAL_ERROR':
                case 'ERROR':
                case 'WARNING':
                case 'EMERGENCY':
                    $content = "[{$type}]: {$this->step_header} DESC: {$msg}" . PHP_EOL;
                    break;
                default:
                    $content = "[{$type}]: {$msg}" . PHP_EOL;
            }
        } else if (getenv('AUTOINSTALLER_VERSION')) {
            $content = "{$type}: {$msg}" . PHP_EOL;
        } else {
            $date = date('Y-m-d h:i:s');
            $content = "[{$date}][{$type}] {$msg}" . PHP_EOL;
        }

        return $content;
    }

    public function write($content, $file = null, $mode='a+')
    {
        $logfile = $file ? $file : $this->logfile;
        $fp = fopen($logfile, $mode);
        fwrite($fp, $content);
        fclose($fp);
    }
}

class PleskDb
{
    var $_db = null;

    function PleskDb($dbParams)
    {
        switch($dbParams['db_type']) {
            case 'mysql':
                $this->_db = new DbMysql(
                    $dbParams['host'], $dbParams['login'], $dbParams['passwd'], $dbParams['db'], $dbParams['port']
                );
                break;

            case 'jet':
                $this->_db = new DbJet($dbParams['db']);
                break;

            case 'mssql':
                $this->_db = new DbMsSql(
                    $dbParams['host'], $dbParams['login'], $dbParams['passwd'], $dbParams['db'], $dbParams['port']
                );
                break;

            default:
                fatal("{$dbParams['db_type']} is not implemented yet");
                break;
        }
    }

    function getInstance()
    {
        global $options;
        static $_instance = array();

        $dbParams['db_type']= Util::getPleskDbType();
        $dbParams['db']     = Util::getPleskDbName();
        $dbParams['port']   = Util::getPleskDbPort();
        $dbParams['login']  = Util::getPleskDbLogin();
        $dbParams['passwd'] = $options->getDbPasswd();
        $dbParams['host']   = Util::getPleskDbHost();

        $dbId = md5(implode("\n", $dbParams));

		$_instance[$dbId] = new PleskDb($dbParams);

        return $_instance[$dbId];
    }

    function fetchOne($sql)
    {
        if (DEBUG) {
            $log = Log::getInstance();
            $log->info($sql);
        }
        return $this->_db->fetchOne($sql);
    }

    function fetchRow($sql)
    {
        $res = $this->fetchAll($sql);
        if (is_array($res) && isset($res[0])) {
            return $res[0];
        }
        return array();
    }

    function fetchAll($sql)
    {
        if (DEBUG) {
            $log = Log::getInstance();
            $log->info($sql);
        }
        return $this->_db->fetchAll($sql);
    }
}

class DbMysql
{
    var $_dbHandler = null;

    function DbMysql($host, $user, $passwd, $database, $port)
    {
        if ( extension_loaded('mysql') ) {
            $this->_dbHandler = @mysql_connect("{$host}:{$port}", $user, $passwd);
            if (!is_resource($this->_dbHandler)) {
                $mysqlError = mysql_error();
                if (stristr($mysqlError, 'access denied for user')) {
                    $errMsg = 'Given <password> is incorrect. ' . $mysqlError;
                } else {
                    $errMsg = 'Unable to connect database. The reason of problem: ' . $mysqlError . PHP_EOL;
                }
                $this->_logError($errMsg);
            }
            @mysql_select_db($database, $this->_dbHandler);
        } else if ( extension_loaded('mysqli') ) {

            $this->_dbHandler = @mysqli_connect($host, $user, $passwd, $database, $port);
            if (!$this->_dbHandler) {
                $mysqlError = mysqli_connect_error();
                if (stristr($mysqlError, 'access denied for user')) {
                    $errMsg = 'Given <password> is incorrect. ' . $mysqlError;
                } else {
                    $errMsg = 'Unable to connect database. The reason of problem: ' . $mysqlError . PHP_EOL;
                }
                $this->_logError($errMsg);
            }
        } else {
            fatal("No MySQL extension is available");
        }
    }

    function fetchAll($sql)
    {
        if ( extension_loaded('mysql') ) {
            $res = mysql_query($sql, $this->_dbHandler);
            if (!is_resource($res)) {
                $this->_logError('Unable to execute query. Error: ' . mysql_error($this->_dbHandler));
            }
            $rowset = array();
            while ($row = mysql_fetch_assoc($res)) {
                $rowset[] = $row;
            }
            return $rowset;
        } else if ( extension_loaded('mysqli') ) {
            $res = $this->_dbHandler->query($sql);
            if ($res === false) {
                $this->_logError('Unable to execute query. Error: ' . mysqli_error($this->_dbHandler));
            }
            $rowset = array();
            while ($row = mysqli_fetch_assoc($res)) {
                $rowset[] = $row;
            }
            return $rowset;
        } else {
            fatal("No MySQL extension is available");
        }
    }

    function fetchOne($sql)
    {
        if ( extension_loaded('mysql') ) {
            $res = mysql_query($sql, $this->_dbHandler);
            if (!is_resource($res)) {
                $this->_logError('Unable to execute query. Error: ' . mysql_error($this->_dbHandler));
            }
            $row = mysql_fetch_row($res);
            return $row[0];
        } else if ( extension_loaded('mysqli') ) {
            $res = $this->_dbHandler->query($sql);
            if ($res === false) {
                $this->_logError('Unable to execute query. Error: ' . mysqli_error($this->_dbHandler));
            }
            $row = mysqli_fetch_row($res);
            return $row[0];
        } else {
            fatal("No MySQL extension is available");
        }
    }

    function query($sql)
    {
        if ( extension_loaded('mysql') ) {
            $res = mysql_query($sql, $this->_dbHandler);
            if ($res === false ) {
                $this->_logError('Unable to execute query. Error: ' . mysql_error($this->_dbHandler) );
            }
            return $res;
        } else if ( extension_loaded('mysqli') ) {
            $res = $this->_dbHandler->query($sql);
            if ($res === false ) {
                $this->_logError('Unable to execute query. Error: ' . mysqli_error($this->_dbHandler) );
            }
            return $res;
        } else {
            fatal("No MySQL extension is available");
        }
    }

    function _logError($message)
    {
        fatal("[MYSQL ERROR] $message");
    }
}

class DbClientMysql extends DbMysql
{
    var $errors = array();

    function _logError($message)
    {
        $message = "[MYSQL ERROR] $message";
        $log = Log::getInstance();
        $log->warning($message);
        $this->errors[] = $message;
    }

    function hasErrors() {
        return count($this->errors) > 0;
    }
}

class DbJet
{
    var $_dbHandler = null;

    function DbJet($dbPath)
    {
        $dsn = "Provider='Microsoft.Jet.OLEDB.4.0';Data Source={$dbPath}";
        $this->_dbHandler = new COM("ADODB.Connection", NULL, CP_UTF8);
        if (!$this->_dbHandler) {
            $this->_logError('Unable to init ADODB.Connection');
        }

        $this->_dbHandler->open($dsn);
    }

    function fetchAll($sql)
    {
        $result_id = $this->_dbHandler->execute($sql);
        if (!$result_id) {
            $this->_logError('Unable to execute sql query ' . $sql);
        }
		if ($result_id->BOF && !$result_id->EOF) {
            $result_id->MoveFirst();
		}
		if ($result_id->EOF) {
		    return array();
		}

		$rowset = array();
		while(!$result_id->EOF) {
    		$row = array();
    		for ($i=0;$i<$result_id->Fields->count;$i++) {
                $field = $result_id->Fields($i);
                $row[$field->Name] = (string)$field->value;
    		}
    		$result_id->MoveNext();
    		$rowset[] = $row;
		}
		return $rowset;
    }

    function fetchOne($sql)
    {
        $result_id = $this->_dbHandler->execute($sql);
        if (!$result_id) {
            $this->_logError('Unable to execute sql query ' . $sql);
        }
		if ($result_id->BOF && !$result_id->EOF) {
            $result_id->MoveFirst();
		}
		if ($result_id->EOF) {
            return null;
		}
        $field = $result_id->Fields(0);
        $result = $field->value;

        return (string)$result;
    }

    function _logError($message)
    {
        fatal("[JET ERROR] $message");
    }
}

class DbMsSql extends DbJet
{
    function DbMsSql($host, $user, $passwd, $database, $port)
    {
        $dsn = "Provider=SQLOLEDB.1;Initial Catalog={$database};Data Source={$host}";
        $this->_dbHandler = new COM("ADODB.Connection", NULL, CP_UTF8);
        if (!$this->_dbHandler) {
            $this->_logError('Unable to init ADODB.Connection');
        }
        $this->_dbHandler->open($dsn, $user, $passwd);
    }

    function _logError($message)
    {
        fatal("[MSSQL ERROR] $message");
    }
}

class Util
{
    function isWindows()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return true;
        }
        return false;
    }

    function isLinux()
    {
        return !Util::isWindows();
    }

    function isVz()
    {
        $vz = false;
        if (Util::isLinux()) {
            if (file_exists('/proc/vz/veredir')) {
                $vz = true;
            }
        } else {
            $reg = 'REG QUERY "HKLM\SOFTWARE\SWsoft\Virtuozzo" 2>nul';
            Util::exec($reg, $code);
            if ($code==0) {
                $vz = true;
            }
        }
        return $vz;
    }

    function getArch()
    {
        global $arch;
        if (!empty($arch))
            return $arch;

        $arch = 'i386';
        if (Util::isLinux()) {
            $cmd = 'uname -m';
            $x86_64 = 'x86_64';
            $output = Util::exec($cmd, $code);
            if (!empty($output) && stristr($output, $x86_64)) {
                $arch = 'x86_64';
            }
        } else {
            $cmd = 'systeminfo';
            $output = Util::exec($cmd, $code);
            if (preg_match('/System Type:[\s]+(.*)/', $output, $macthes) && stristr($macthes[1], '64')) {
                $arch = 'x86_64';
            }
        }
        return $arch;
    }

    function getHostname()
    {
        if (Util::isLinux()) {
            $cmd = 'hostname -f';
        } else {
            $cmd = 'hostname';
        }
        $hostname = Util::exec($cmd, $code);

        if (empty($hostname)) {
        	$err = 'Command: ' . $cmd . ' returns: ' . $hostname . "\n";
        	$err .= 'Hostname is not defined and configured. Unable to get hostname. Server should have properly configured hostname and it should be resolved locally.';
            fatal($err);
        }

        return $hostname;
    }

    function isFQDN($string)
    {
    	$tld_list = array(
                'aero', 'asia', 'biz', 'cat', 'com', 'coop', 'edu', 'gov', 'info', 'int', 'jobs', 'mil', 'mobi', 'museum', 'name', 'net',
    			'org', 'pro', 'tel', 'travel', 'xxx', 'ac', 'ad', 'ae', 'af', 'ag', 'ai', 'al', 'am', 'an', 'ao', 'aq', 'ar', 'as', 'at',
    			'au', 'aw', 'ax', 'az', 'ba', 'bb', 'bd', 'be', 'bf', 'bg', 'bh', 'bi', 'bj', 'bm', 'bn', 'bo', 'br', 'bs', 'bt', 'bv',
    			'bw', 'by', 'bz', 'ca', 'cc', 'cd', 'cf', 'cg', 'ch', 'ci', 'ck', 'cl', 'cm', 'cn', 'co', 'cr', 'cs', 'cu', 'cv', 'cx',
    			'cy', 'cz', 'dd', 'de', 'dj', 'dk', 'dm', 'do', 'dz', 'ec', 'ee', 'eg', 'eh', 'er', 'es', 'et', 'eu', 'fi', 'fj', 'fk',
    			'fm', 'fo', 'fr', 'ga', 'gb', 'gd', 'ge', 'gf', 'gg', 'gh', 'gi', 'gl', 'gm', 'gn', 'gp', 'gq', 'gr', 'gs', 'gt', 'gu',
    			'gw', 'gy', 'hk', 'hm', 'hn', 'hr', 'ht', 'hu', 'id', 'ie', 'il', 'im', 'in', 'io', 'iq', 'ir', 'is', 'it', 'je', 'jm',
    			'jo', 'jp', 'ke', 'kg', 'kh', 'ki', 'km', 'kn', 'kp', 'kr', 'kw', 'ky', 'kz', 'la', 'lb', 'lc', 'li', 'lk', 'lr', 'ls',
    			'lt', 'lu', 'lv', 'ly', 'ma', 'mc', 'md', 'me', 'mg', 'mh', 'mk', 'ml', 'mm', 'mn', 'mo', 'mp', 'mq', 'mr', 'ms', 'mt',
    			'mu', 'mv', 'mw', 'mx', 'my', 'mz', 'na', 'nc', 'ne', 'nf', 'ng', 'ni', 'nl', 'no', 'np', 'nr', 'nu', 'nz', 'om', 'pa',
    			'pe', 'pf', 'pg', 'ph', 'pk', 'pl', 'pm', 'pn', 'pr', 'ps', 'pt', 'pw', 'py', 'qa', 're', 'ro', 'rs', 'ru', 'rw', 'sa',
    			'sb', 'sc', 'sd', 'se', 'sg', 'sh', 'si', 'sj', 'sk', 'sl', 'sm', 'sn', 'so', 'sr', 'ss', 'st', 'su', 'sv', 'sy', 'sz',
    			'tc', 'td', 'tf', 'tg', 'th', 'tj', 'tk', 'tl', 'tm', 'tn', 'to', 'tp', 'tr', 'tt', 'tv', 'tw', 'tz', 'ua', 'ug', 'uk',
    			'us', 'uy', 'uz', 'va', 'vc', 've', 'vg', 'vi', 'vn', 'vu', 'wf', 'ws', 'ye', 'yt', 'za', 'zm', 'zw' );

    	$label = '[a-zA-Z0-9\-]{1,62}\.';
    	$tld = '[\w]+';
    	if(preg_match( '/^(' . $label. ')+(' . $tld . ')$/', $string, $match ) && in_array( $match[2], $tld_list )) {
    		return TRUE;
    	} else {
    		return FALSE;
    	}

    }

    function resolveHostname($hostname)
    {
    	$dns_record = @dns_get_record($hostname, DNS_A | DNS_AAAA);
        if (false === $dns_record) {
            $error = error_get_last();
            Log::getInstance()->warning(sprintf(
                "Unable to resolve hostname \"%s\": %s\nMake sure that the operating system`s DNS resolver is set up and works properly."
                , $hostname
                , $error['message']
            ));
            return null;
        }

    	if (isset($dns_record[0]['ip'])) {
    		return $dns_record[0]['ip'];
    	}
    	if (isset($dns_record[0]["ipv6"])) {
    		return $dns_record[0]['ipv6'];
    	}

    	return null;
    }

    function getIP()
    {
        $list = Util::getIPList();
        return $list[0]; //main IP
    }

    function getIPList($lo=false)
    {
        if (Util::isLinux()) {
            $ipList = Util::getIPv4ListOnLinux();
            foreach ($ipList as $key => $ip) {
                if (!$lo && substr($ip, 0, 3) == '127') {
                    unset($ipList[$key]);
                    continue;
                }
                trim($ip);
            }
            $ipList = array_values($ipList);
        } else {
            $cmd = 'hostname';
            $hostname = Util::exec($cmd, $code);
            $ip = gethostbyname($hostname);
            $res = ($ip != $hostname) ? true : false;
            if (!$res) {
                fatal('Unable to retrieve IP address');
            }
            $ipList = array(trim($ip));
        }
        return $ipList;
    }

    function getIPv6ListOnLinux()
    {
        return Util::grepCommandOutput(array(
            array('bin' => 'ip', 'command' => '%PATH% addr list', 'regexp' => '#inet6 ([^ /]+)#'),
            array('bin' => 'ifconfig', 'command' => '%PATH% -a', 'regexp' => '#inet6 (?:addr: ?)?([A-F0-9:]+)#i'),
        ));
    }

    function getIPv4ListOnLinux()
    {
        $commands = array(
            array('bin' => 'ip', 'command' => '%PATH% addr list', 'regexp' => '#inet ([^ /]+)#'),
            array('bin' => 'ifconfig', 'command' => '%PATH% -a', 'regexp' => '#inet (?:addr: ?)?([\d\.]+)#'),
        );
        if (!($list = Util::grepCommandOutput($commands))) {
            fatal('Unable to get IP address');
        }
        return $list;
    }

    function grepCommandOutput($cmds)
    {
        foreach ($cmds as $cmd) {
            if ($fullPath = Util::lookupCommand($cmd['bin'])) {
                $output = Util::exec(str_replace("%PATH%", $fullPath, $cmd['command']), $code);
                if (preg_match_all($cmd['regexp'], $output, $matches)) {
                    return $matches[1];
                }
            }
        }
        return false;
    }

    function getIPListOnWindows()
    {
    	$cmd = 'wmic.exe path win32_NetworkAdapterConfiguration get IPaddress';
    	$output = Util::exec($cmd, $code);
    	if (!preg_match_all('/"(.*?)"/', $output, $matches)) {
    		fatal('Unable to get IP address');
    	}
    	return $matches[1];
    }

    function getPleskRootPath()
    {
        global $_pleskRootPath;
        if (empty($_pleskRootPath)) {
            if (Util::isLinux()) {
                if (PleskOS::isDebLike()) {
                    $_pleskRootPath = '/opt/psa';
                } else {
                    $_pleskRootPath = '/usr/local/psa';
                }
            }
            if (Util::isWindows()) {
                $_pleskRootPath = Util::regPleskQuery('PRODUCT_ROOT_D', true);
            }
        }
        return $_pleskRootPath;
    }

    function getPleskDbName()
    {
        $dbName = 'psa';
        if (Util::isWindows()) {
            $dbName = Util::regPleskQuery('mySQLDBName');
        }
        return $dbName;
    }

    function getPleskDbLogin()
    {
        $dbLogin = 'admin';
        if (Util::isWindows()) {
            $dbLogin = Util::regPleskQuery('PLESK_DATABASE_LOGIN');
        }
        return $dbLogin;
    }

    function getPleskDbType()
    {
        $dbType = 'mysql';
        if (Util::isWindows()) {
            $dbType = strtolower(Util::regPleskQuery('PLESK_DATABASE_PROVIDER_NAME'));
        }
        return $dbType;
    }

    function getPleskDbHost()
    {
    	$dbHost = 'localhost';
    	if (Util::isWindows()) {
    		$dbProvider = strtolower(Util::regPleskQuery('PLESK_DATABASE_PROVIDER_NAME'));
    		if ($dbProvider == 'mysql' || $dbProvider == 'mssql') {
    			$dbHost = Util::regPleskQuery('MySQL_DB_HOST');
    		}
    	}
    	return $dbHost;
    }

    function getPleskDbPort()
    {
        $dbPort = '3306';
        if (Util::isWindows()) {
            $dbPort = Util::regPleskQuery('MYSQL_PORT');
        }
        return $dbPort;
    }

    function regPleskQuery($key, $returnResult=false)
    {
        $arch = Util::getArch();
        if ($arch == 'x86_64') {
            $reg = 'REG QUERY "HKLM\SOFTWARE\Wow6432Node\Plesk\Psa Config\Config" /v '.$key;
        } else {
            $reg = 'REG QUERY "HKLM\SOFTWARE\Plesk\Psa Config\Config" /v '.$key;
        }
        $output = Util::exec($reg, $code);

        if ($returnResult && $code!=0) {
            return false;
        }

        if ($code!=0) {
            $log = Log::getInstance();
            $log->info($reg);
            $log->info($output);
            fatal("Unable to get '$key' from registry");
        }
        if (!preg_match("/\w+\s+REG_SZ\s+(.*)/i", trim($output), $matches)) {
            fatal('Unable to macth registry value by key '.$key.'. Output: ' .  trim($output));
        }

        return $matches[1];
    }

    function regQuery($path, $key, $returnResult=false)
    {
    	$arch = Util::getArch();
    	if ($arch == 'x86_64') {
    		$reg = 'REG QUERY "HKLM\SOFTWARE\Wow6432Node' . $path .  '" '.$key;
    	} else {
    		$reg = 'REG QUERY "HKLM\SOFTWARE' . $path .  '" '.$key;
    	}
    	$output = Util::exec($reg, $code);

    	if ($returnResult && $code!=0) {
    		return false;
    	}


    	if ($code!=0) {
            $log = Log::getInstance();
    		$log->info($reg);
    		$log->info($output);
    		fatal("Unable to get '$key' from registry");
    	}
		
    	if (!preg_match("/\s+REG_SZ(\s+)?(.*)/i", trim($output), $matches)) {
			fatal('Unable to match registry value by key '.$key.'. Output: ' .  trim($output));
		}

    	return $matches[2];
    }

    function getAutoinstallerVersion()
    {
    	if (Util::isLinux()) {
    		$rootPath = Util::getPleskRootPath();
    		$cmd = $rootPath . '/admin/sbin/autoinstaller --version';
    		$output = Util::exec($cmd, $code);
    	} else {
    		$cmd = '"' . Util::regPleskQuery('PRODUCT_ROOT_D', true) . 'admin\bin\ai.exe" --version';
    		$output = Util::exec($cmd, $code);
    	}
    	if (!preg_match("/\d+\.\d+\.\d+/", trim($output), $matches)) {
    		fatal('Unable to match autoinstaller version. Output: ' .  trim($output));
    	}
    	return $matches[0];
    }

	function getAutointallerVersionEnv()
	{
		return getenv('AUTOINSTALLER_VERSION');
	}
	
    function lookupCommand($cmd, $exit = false, $path = '/bin:/usr/bin:/usr/local/bin:/usr/sbin:/sbin:/usr/local/sbin')
    {
        $dirs = explode(':', $path);
        foreach ($dirs as $dir) {
            $util = $dir . '/' . $cmd;
            if (is_executable($util)) {
                return $util;
            }
        }
        if ($exit) {
            fatal("{$cmd}: command not found");
        }
        return false;
    }

    function getSystemDisk()
    {
    	$cmd = 'echo %SYSTEMROOT%';
    	$output = Util::exec($cmd, $code);
    	return substr($output, 0, 3);
    }

    function getSystemRoot()
    {
    	$cmd = 'echo %SYSTEMROOT%';
    	$output = Util::exec($cmd, $code);
    	return $output;
    }

    function getFileVersion($file)
    {
    	$fso = new COM("Scripting.FileSystemObject");
    	$version = $fso->GetFileVersion($file);
    	$fso = null;
    	return $version;
    }

    function isUnknownISAPIfilters()
    {
        $log = Log::getInstance();

        $isUnknownISAPI = false;
        $knownISAPI = array ("ASP\\.Net.*", "sitepreview", "COMPRESSION", "jakarta");

        foreach ($knownISAPI as &$value) {
            $value = strtoupper($value);
        }
        $cmd='cscript ' . Util::getSystemDisk() . 'inetpub\AdminScripts\adsutil.vbs  ENUM W3SVC/FILTERS';
        $output = Util::exec($cmd,  $code);

        if ($code!=0) {
            $log->info("Unable to get ISAPI filters. Error: " . $output);
            return false;
        }
        if (!preg_match_all('/FILTERS\/(.*)]/', trim($output), $matches)) {
            $log->info($output);
            $log->info("Unable to get ISAPI filters from output: " . $output);
            return false;
        }
        foreach ($matches[1] as $ISAPI) {
            $valid = false;
            foreach ($knownISAPI as $knownPattern) {
                if (preg_match("/$knownPattern/i", $ISAPI)) {
                    $valid = true;
                    break;
                }
            }
            if (! $valid ) {
                $log->warning("Unknown ISAPI filter detected in IIS: " . $ISAPI);
                $isUnknownISAPI = true;
            }
        }

        return $isUnknownISAPI;
    }

    function getMySQLServerVersion()
    {
    	$credentials = Util::getDefaultClientMySQLServerCredentials();
		
    	if (!Util::isLinux() && (preg_match('/AES-128-CBC/', $credentials['admin_password']))) {
            $log = Log::getInstance();
    		$log->info('The administrator\'s password for the default MySQL server is encrypted.');
    		return;
    	}

    	$mysql = new DbClientMysql('localhost', $credentials['admin_login'], $credentials['admin_password'] , 'information_schema', 3306);
    	if (!$mysql->hasErrors()) {
    		$sql = 'select version()';
    		$mySQLversion = $mysql->fetchOne($sql);
    		if (!preg_match("/(\d{1,})\.(\d{1,})\.(\d{1,})/", trim($mySQLversion), $matches)) {
    			fatal('Unable to match MySQL server version.');
    		}
    		return $matches[0];
    	}
    }

    function getDefaultClientMySQLServerCredentials()
    {
    	$db = PleskDb::getInstance();
		
		$sql = "SELECT val FROM misc WHERE param='default_server_mysql'";
		$defaultServerMysqlId = $db->fetchOne($sql);
		if ($defaultServerMysqlId) {
			$sql = "SELECT DatabaseServers.admin_login, DatabaseServers.admin_password FROM DatabaseServers WHERE id=${defaultServerMysqlId}";
		} else {
		   	$sql = "SELECT DatabaseServers.admin_login, DatabaseServers.admin_password FROM DatabaseServers WHERE type='mysql' AND host='localhost'";
		}
    	$clientDBServerCredentials = $db->fetchAll($sql);
    	if (Util::isLinux()) {
    		$clientDBServerCredentials[0]['admin_password'] = Util::retrieveAdminMySQLDbPassword();
    	}
     	return $clientDBServerCredentials[0];
    }

	function retrieveAdminMySQLDbPassword()
	{
		if (Util::isLinux())
			return trim( Util::readfile("/etc/psa/.psa.shadow") );
		else
			return null;
	}

    function exec($cmd, &$code)
    {
        $log = Log::getInstance();

        if (!$cmd) {
            $log->info('Unable to execute a blank command. Please see ' . LOG_PATH . ' for details.');

            $debugBacktrace = "";
            foreach (debug_backtrace() as $i => $obj) {
                $debugBacktrace .= "#{$i} {$obj['file']}:{$obj['line']} {$obj['function']} ()\n";
            }
            $log->debug("Unable to execute a blank command. The stack trace:\n{$debugBacktrace}");
            $code = 1;
            return '';
        }
        exec($cmd, $output, $code);
        return trim(implode("\n", $output));
    }

	function readfile($file)
	{
		if (!is_file($file) || !is_readable($file))
			return null;
		$lines = file($file);
		if ($lines === false)
			return null;
		return trim(implode("\n", $lines));
	}

	function readfileToArray($file)
	{
		if (!is_file($file) || !is_readable($file))
			return null;
		$lines = file($file);
		if ($lines === false)
			return null;
		return $lines;
	}

	function getSettingFromPsaConf($setting)
	{
		$file = '/etc/psa/psa.conf';
		if (!is_file($file) || !is_readable($file))
			return null;
		$lines = file($file);
		if ($lines === false)
			return null;
		foreach ($lines as $line) {
			if (preg_match("/^{$setting}\s.*/", $line, $match_setting)) {
				if (preg_match("/[\s].*/i", $match_setting[0], $match_value)) {
					$value = trim($match_value[0]);
					return $value;
				}
			}
		}
		return null;
	}

	function GetFreeSystemMemory()
	{
		if (Util::isLinux()) {
			$cmd = 'cat /proc/meminfo';
			$output = Util::exec($cmd, $code);
			if (preg_match("/MemFree:.+?(\d+)/", $output, $MemFree)) {
				if (preg_match("/SwapFree:.+?(\d+)/", $output, $SwapFree)) {
					return $MemFree[1] + $SwapFree[1]; // returns value in Kb
				}
			}
		} else {
			$cmd = 'wmic.exe OS get FreePhysicalMemory';
			$output = Util::exec($cmd, $code);
			if (preg_match("/\d+/", $output, $FreePhysicalMemory)) {
				$cmd = 'wmic.exe PAGEFILE get AllocatedBaseSize';
				$output = Util::exec($cmd, $code);
				if (preg_match("/\d+/", $output, $SwapAllocatedBaseSize)) {
					$cmd = 'wmic.exe PAGEFILE get CurrentUsage';
					$output = Util::exec($cmd, $code);
					if (preg_match("/\d+/", $output, $SwapCurrentUsage)) {
						return $FreePhysicalMemory[0] + ($SwapAllocatedBaseSize[0] - $SwapCurrentUsage[0]) * 1000; // returns value in Kb
					}
				}
			}
		}
	}

	function getPhpIni()
	{
		if (Util::isLinux()) {
			// Debian/Ubuntu  /etc/php5/apache2/php.ini /etc/php5/conf.d/
			// SuSE  /etc/php5/apache2/php.ini /etc/php5/conf.d/
			// CentOS 4/5 /etc/php.ini /etc/php.d
			if (PleskOS::isRedHatLike()) {
				$phpini = Util::readfileToArray('/etc/php.ini');
			} else {
				$phpini = Util::readfileToArray('/etc/php5/apache2/php.ini');
			}
		}

		return $phpini;
	}
	
	function getUserBeanCounters()
	{
		if (!Util::isLinux()) {
			
			return false;
		}
		$user_beancounters = array();
		$ubRaw = Util::readfileToArray('/proc/user_beancounters');
		
    	if (!$ubRaw) {
			
			return false;
		}
   		for ($i=2; $i<=count($ubRaw)-1; $i++) {
			
   			if (preg_match('/^.+?:?.+?\b(\w+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $ubRaw[$i], $limit_name)) {
				
				$user_beancounters[trim($limit_name[1])] = array(
					'held' => (int)$limit_name[2],
					'maxheld' => (int)$limit_name[3],
					'barrier' => (int)$limit_name[4],
					'limit' => (int)$limit_name[5],
					'failcnt' => (int)$limit_name[6]
				);
   			}
   		}
		
		return $user_beancounters;
	}
}

class PackageManager
{
	function buildListCmdLine($glob)
	{
		if (PleskOS::isRedHatLike() || PleskOS::isSuseLike()) {
			$cmd = "rpm -qa --queryformat '%{NAME} %{VERSION}-%{RELEASE} %{ARCH}\\n'";
		} elseif (PleskOS::isDebLike()) {
			$cmd = "dpkg-query --show --showformat '\${Package} \${Version} \${Architecture}\\n'";
		} else {
			return false;
		}

		if (!empty($glob)) {
			$cmd .= " '" . $glob . "' 2>/dev/null";
		}

		return $cmd;
	}

	/*
	 * Fetches a list of installed packages that match given criteria.
	 * string $glob - Glob (wildcard) pattern for coarse-grained packages selection from system package management backend. Empty $glob will fetch everything.
	 * string $regexp - Package name regular expression for a fine-grained filtering of the results.
	 * returns array of hashes with keys 'name', 'version' and 'arch', or false on error.
	 */
	function listInstalled($glob, $regexp = null)
	{
		$cmd = PackageManager::buildListCmdLine($glob);
        if (!$cmd) {
            return array();
        }

		$output = Util::exec($cmd, $code);
		if ($code != 0) {
			return false;
		}

		$packages = array();
		$lines = explode("\n", $output);
		foreach ($lines as $line) {
			@list($pkgName, $pkgVersion, $pkgArch) = explode(" ", $line);
			if (empty($pkgName) || empty($pkgVersion) || empty($pkgArch))
				continue;
			if (!empty($regexp) && !preg_match($regexp, $pkgName))
				continue;
			$packages[] = array(
				'name' => $pkgName,
				'version' => $pkgVersion,
				'arch' => $pkgArch
			);
		}

		return $packages;
	}

	function isInstalled($glob, $regexp = null)
	{
		$packages = PackageManager::listInstalled($glob, $regexp);
		return !empty($packages);
	}
}

class Package
{
	function getManager($field, $package)
	{
	    $redhat = 'rpm -q --queryformat \'%{' . $field . '}\n\' ' . $package;
    	$debian = 'dpkg-query --show --showformat=\'${' . $field . '}\n\' '. $package . ' 2> /dev/null';
    	$suse = 'rpm -q --queryformat \'%{' . $field . '}\n\' ' . $package;
    	$manager = false;

    	if (PleskOS::isRedHatLike()) {
    		$manager = $redhat;
    	} elseif (PleskOS::isDebLike()) {
    		$manager = $debian;
    	} elseif (PleskOS::isSuseLike()) {
    		$manager = $suse;
    	} else {
    		return false;
    	}

    	return $manager;
	}

	/* DPKG doesn't supports ${Release}
	 *
	 */

	function getRelease($package)
	{
		$release = false;

		$manager = Package::getManager('Release', $package);

		if (!$manager) {
			return false;
		}

		$release = Util::exec($manager, $code);
		if (!$code === 0) {
			return false;
		}
		return $release;
	}

	function getVersion($package)
	{
		$version = false;

		$manager = Package::getManager('Version', $package);

		if (!$manager) {
			return false;
		}

		$version = Util::exec($manager, $code);
		if (!$code === 0) {
			return false;
		}
		return $version;
	}

}

class PleskOS
{
    function isSuse103()
    {
        return PleskOS::_detectOS('suse', '10.3');
    }

    function isUbuntu804()
    {
        return PleskOS::_detectOS('ubuntu', '8.04');
    }

    function isDebLike()
    {
    	if (PleskOS::_detectOS('ubuntu', '.*')
    	|| PleskOS::_detectOS('debian', '.*')
    	) {
    		return true;
    	}
    	return false;
    }

    function isSuseLike()
    {
    	if (PleskOS::_detectOS('suse', '.*')) {
    		return true;
    	}
    	return false;
    }

    function isRedHatLike()
    {
		return (PleskOS::isRedHat() || PleskOS::isCentOS() || PleskOS::isCloudLinux());
    }


    function isRedHat()
    {
    	if (PleskOS::_detectOS('red\s*hat', '.*')) {
    		return true;
    	}
    	return false;
    }

	function isCloudLinux()
	{
		return PleskOS::_detectOS('CloudLinux', '.*');
	}

    function isCentOS()
    {
    	if (PleskOS::_detectOS('centos', '.*')) {
    		return true;
    	}
    	return false;
    }
	
	function isCentOS5()
    {
    	if (PleskOS::_detectOS('centos', '5.*')) {
    		return true;
    	}
    	return false;
    }


    function _detectOS($name, $version)
    {
        foreach (array(PleskOs::catPsaVersion(), PleskOS::catEtcIssue()) as $output) {
            if (preg_match("/{$name}[\s]+$version/i", $output)) {
                return true;
            }
        }
        return false;
    }

    function catPsaVersion()
    {
        if (is_file('/usr/local/psa/version')) {
            $cmd = 'cat /usr/local/psa/version';
        } elseif (is_file('/opt/psa/version')) {
            $cmd = 'cat /opt/psa/version';
        } else {
            return '';
        }
        $output = Util::exec($cmd, $code);

        return $output;
    }

    function catEtcIssue()
    {
        $cmd = 'cat /etc/issue';
        $output = Util::exec($cmd, $code);

        return $output;
    }

    function detectSystem()
    {
        $log = Log::getInstance('Detect system configuration');
        $log->info('OS: ' . (Util::isLinux() ? PleskOS::catEtcIssue() : 'Windows'));
        $log->info('Arch: ' . Util::getArch());
    }
}

class PleskValidator
{
    function isValidIp($value)
    {
        if (!is_string($value)) {
            return false;
        }
        if (!PleskValidator::validateIPv4($value) && !PleskValidator::validateIPv6($value)) {
            return false;
        }
        return true;
    }

    function validateIPv4($value)
    {
        $ip2long = ip2long($value);
        if ($ip2long === false) {
            return false;
        }

        return $value == long2ip($ip2long);
    }

    function validateIPv6($value)
    {
        if (strlen($value) < 3) {
            return $value == '::';
        }

        if (strpos($value, '.')) {
            $lastcolon = strrpos($value, ':');
            if (!($lastcolon && PleskValidator::validateIPv4(substr($value, $lastcolon + 1)))) {
                return false;
            }

            $value = substr($value, 0, $lastcolon) . ':0:0';
        }

        if (strpos($value, '::') === false) {
            return preg_match('/\A(?:[a-f0-9]{1,4}:){7}[a-f0-9]{1,4}\z/i', $value);
        }

        $colonCount = substr_count($value, ':');
        if ($colonCount < 8) {
            return preg_match('/\A(?::|(?:[a-f0-9]{1,4}:)+):(?:(?:[a-f0-9]{1,4}:)*[a-f0-9]{1,4})?\z/i', $value);
        }

        // special case with ending or starting double colon
        if ($colonCount == 8) {
            return preg_match('/\A(?:::)?(?:[a-f0-9]{1,4}:){6}[a-f0-9]{1,4}(?:::)?\z/i', $value);
        }

        return false;
    }
}

class CheckRequirements
{
    function validate()
    {
        if (!PleskInstallation::isInstalled()) {
            //:INFO: skip chking mysql extension if plesk is not installed
            return;
        }

        $reqExts = array();
        foreach ($reqExts as $name) {
            $status = extension_loaded($name);
            if (!$status) {
                $this->_fail("PHP extension {$name} is not installed");
            }
        }
    }

    function _fail($errMsg)
    {
        echo '===Checking requirements===' . PHP_EOL;
        echo PHP_EOL . 'Error: ' . $errMsg . PHP_EOL;
        exit(1);
    }
}

class GetOpt
{
    var $_argv = null;
	var $_adminDbPasswd = null;

    function GetOpt()
    {
        $this->_argv = $_SERVER['argv'];
		if (empty($this->_argv[1]) && Util::isLinux())
			$this->_adminDbPasswd = Util::retrieveAdminMySQLDbPassword();
		else
			$this->_adminDbPasswd = $this->_argv[1];
    }

    function validate()
    {
        if (empty($this->_adminDbPasswd) && PleskInstallation::isInstalled()) {
            echo 'Please specify Plesk database password';
            $this->_helpUsage();
        }
    }

    function getDbPasswd()
    {
        return $this->_adminDbPasswd;
    }

    function _helpUsage()
    {
        echo PHP_EOL . "Usage: {$this->_argv[0]} <plesk_db_admin_password>" . PHP_EOL;
        exit(1);
    }
}

function fatal($msg)
{
    $log = Log::getInstance();
    $log->fatal($msg);
    exit(1);
}

$log = Log::getInstance();

//:INFO: Validate options
$options = new GetOpt();
$options->validate();

//:INFO: Validate PHP requirements, need to make sure that PHP extensions are installed
$checkRequirements = new CheckRequirements();
$checkRequirements->validate();

//:INFO: Validate Plesk installation
$pleskInstallation = new PleskInstallation();
$pleskInstallation->validate();

//:INFO: Detect system
$pleskOs = new PleskOS();
$pleskOs->detectSystem();

//:INFO: Need to make sure that given db password is valid
if (PleskInstallation::isInstalled()) {
    $log->step('Validating the database password');
    $pleskDb = PleskDb::getInstance();
    $log->resultOk();
}

//:INFO: Dump script version
$log->step('Pre-Upgrade analyzer version: ' . PRE_UPGRADE_SCRIPT_VERSION);

// Check for possible Autoinstaller problems
$aiKnownIssues = new AutoinstallerKnownIssues();
$aiKnownIssues->validate();

//:INFO: Check potential problems you may encounter during transition to Plesk 10 model.
$pleskBusinessModel = new Plesk10BusinessModel();
$pleskBusinessModel->validate();

//:INFO: Validate Plesk requirements before installation/upgrade
$pleskRequirements = new Plesk10Requirements();
$pleskRequirements->validate();

//:INFO: Validate issues related to Mail system
$pleskMailServer = new Plesk10MailServer();
$pleskMailServer->validate();

//:INFO: Validate issues related to Skin
$pleskSkin = new Plesk10Skin();
$pleskSkin->validate();

//:INFO: Validate issues related to Permissions
$pleskPermissions = new Plesk10Permissions();
$pleskPermissions->validate();

//:INFO: Validate known OS specific issues with recommendation to avoid bugs in Plesk
$pleskKnownIssues = new Plesk10KnownIssues();
$pleskKnownIssues->validate();

$log->dumpStatistics();

if ($log->getEmergency() > 0) {
	exit(2);
}

if ($log->getErrors() > 0 || $log->getWarnings() > 0) {
	exit(1);
}
// vim:set et ts=4 sts=4 sw=4:
