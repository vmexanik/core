<?

/**
 * @author Mikhail Starovoyt
 *
 * Class for modules versions and updating project structure due to versions
 */

class Repository extends Base
{
	private static $oInstance = null;
	public $sPrefix = 'repository';

	//-----------------------------------------------------------------------------------------------
	public static function Get(){
		if (!self::$oInstance) {
			self::$oInstance = new self();
		}
		return self::$oInstance;
	}
	//-----------------------------------------------------------------------------------------------
	public function __construct(){
	}
	//-----------------------------------------------------------------------------------------------
	public static function InitDatabase($sModuleName,$bCoreSql=true)
	{
		if ($bCoreSql) $sSqlPath='/class/core/sql/init_database/';
		else $sSqlPath='/include/sql/init_database/';

		if (Base::GetConstant('repository:is_init_mode',1) && !Repository::CheckTableExist($sModuleName)) {
			require(SERVER_PATH.$sSqlPath.$sModuleName.'.php');
			if ($aInitSql)  foreach ($aInitSql as $aValue) {
				Db::Execute($aValue);
			}
		}
	}
	//-----------------------------------------------------------------------------------------------
	public static function CheckTableExist($sTableName)
	{
		return Db::GetOne("
		SELECT COUNT(*) AS count
        FROM information_schema.tables
        WHERE table_schema = '".Base::$db->databaseName."' and table_name = '".$sTableName."'");
	}
	//-----------------------------------------------------------------------------------------------
	/**
	 * Deprecated method
	 */
	//    public static function LoadDump($file, $delimiter = ';')
	//    {
	//        //set_time_limit(0);
	//        if (is_file($file) === true)
	//        {
	//            $file = fopen($file, 'r');
	//            if (is_resource($file) === true)
	//            {
	//                $query = array();
	//                while (feof($file) === false)
	//                {
	//                    $query[] = fgets($file);
	//                    if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1)
	//                    {
	//                        $query = trim(implode('', $query));
	//                        Db::Execute($query);
	//                    }
	//                    if (is_string($query) === true)
	//                    {
	//                        $query = array();
	//                    }
	//                }
	//                return fclose($file);
	//            }
	//        }
	//        return false;
	//    }
	//-----------------------------------------------------------------------------------------------
	/**
	 * To update to new version need to change constant 'module_version_update:{module_name}','{new_updated_version}'
	 *
	 * @param string $sModuleName
	 * @param update script destination $bCoreSql
	 */
	public function CheckUpdate($sModuleName,$bCoreSql=true)
	{
		if ($bCoreSql) $sSqlPath='/class/core/sql/init_database/update/';
		else $sSqlPath='/include/sql/init_database/update/';

		if (Base::GetConstant('repository:is_init_mode',1)) {
			$sOldVersion=Base::GetConstant('module_version:'.$sModuleName);
			$sNewVersion=Base::GetConstant('module_version_update:'.$sModuleName);

			if (!$sNewVersion || $sOldVersion==$sNewVersion) return;

			$sUpdateFullFileName=SERVER_PATH.$sSqlPath.$sModuleName.'_'.$sOldVersion.'_'.$sNewVersion.'.php';
			if (!file_exists($sUpdateFullFileName)) return;

			require($sUpdateFullFileName);
			if ($aUpdateSql)  {
				foreach ($aUpdateSql as $aValue) {
					Db::Execute($aValue);
				}
				Base::UpdateConstant('module_version:'.$sModuleName,$sNewVersion);
			}
		}
	}
	//-----------------------------------------------------------------------------------------------

}

?>