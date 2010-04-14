<?php
# **************************************************************************#
# MolyX2
# ------------------------------------------------------
# @copyright (c) 2009-2010 MolyX Group..
# @official forum http://molyx.com
# @license http://opensource.org/licenses/gpl-2.0.php GNU Public License 2.0
#
# $Id$
# **************************************************************************#
define('IN_SQL', true);
if (isset($_REQUEST['do']) && $_REQUEST['do'] === 'dobackup')
{
	$content_type = true;
}
require ('./global.php');

class mysql
{
	var $dbsql = '';
	var $droptable = 1;
	var $createtable = 1;
	var $tableid = 0;
	var $offset = 0;
	var $skip = 1;
	var $step = 1;
	var $advbackup = 1;
	var $enablegzip = 0;
	var $dbblocksize = 0;
	var $hex_type = 0;
	var $mysql_version = '';

	function show()
	{
		global $forums, $DB, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditmysql'])
		{
			if (!$fp = @fopen(ROOT_PATH . 'data/dbbackup/unlock.dbb', 'r'))
			{
				$forums->admin->print_cp_error($forums->lang['nopermissions']);
			}
			fclose($fp);
		}
		$forums->admin->nav[] = array('mysql.php', $forums->lang['managemysql']);
		$no_array = explode('.', preg_replace('/^(.+?)[\-_]?/', '\\1', $DB->version));
		$one = (!isset($no_array) || !isset($no_array[0])) ? 3 : $no_array[0];
		$two = (!isset($no_array[1])) ? 21 : $no_array[1];
		$three = (!isset($no_array[2])) ? 0 : $no_array[2];
		$this->savedate = isset($_INPUT['savedate']) ? intval($_INPUT['savedate']) : date('Ymd', TIMENOW);
		$this->md5_check = isset($_INPUT['check']) ? trim($_INPUT['check']) : md5(TIMENOW . IPADDRESS);
		$this->mysql_version = (int) sprintf('%d%02d%02d', $one, $two, intval($three));
		switch ($_INPUT['do'])
		{
			case 'dotool':
				$this->sqltool();
				break;
			case 'runtime':
				$this->view_sql('SHOW STATUS');
				break;
			case 'system':
				$this->view_sql('SHOW VARIABLES');
				break;
			case 'processes':
				$this->view_sql('SHOW PROCESSLIST');
				break;
			case 'runsql':
				$q = ($_POST['query'] == '') ? rawurldecode($_GET['query']) : $_POST['query'];
				$this->view_sql(trim($q));
				break;
			case 'backup':
				$this->backup_form();
				break;
			case 'restore':
				$this->restore_form();
				break;
			case 'pttable':
				$this->pttable_form();
				break;
			case 'movedata':
				$this->movedata_form();
				break;
			case 'confirmrestore':
				$this->confirmrestore();
				break;
			case 'confirmbackup':
				$this->confirmbackup();
				break;
			case 'dobackup':
				$this->dobackup();
				break;
			case 'dorestore':
				$this->dorestore();
				break;
			case 'export_tbl':
				$this->dobackup(trim(rawurldecode($_GET['tbl'])));
				break;
			case 'delsql':
				$this->dodeletesql();
				break;
			case 'changepttable':
				$this->dopttable('change');
				break;
			case 'createpttable':
				$this->dopttable('create');
				break;
			case 'domovedata':
				$this->domovedata();
				break;
			default:
				$this->sqlmain();
				break;
		}
	}

	function dobackup($tbl_name = '')
	{
		global $forums, $DB, $_INPUT;
		$this->onlymolyx = intval($_INPUT['onlymolyx']);
		$this->skip = intval($_INPUT['skip']);
		$this->createtable = intval($_INPUT['createtable']);
		$this->droptable = intval($_INPUT['droptable']);
		$this->enablegzip = intval($_INPUT['enablegzip']);
		$this->advbackup = intval($_INPUT['advbackup']);
		$this->hex_type = intval($_INPUT['hex_type']);
		if ($tbl_name == '')
		{
			$filename = 'molyx_dbbackup';
		}
		else
		{
			$filename = $tbl_name;
		}
		$this->noshow = false;
		$DB->set_sql_mode('MYSQL40');
		if ($this->advbackup)
		{
			return $this->doadvbackup();
		}
		$output = '';
		@header('Pragma: no-cache');
		$do_gzip = 0;
		if ($this->enablegzip)
		{
			if (extension_loaded("zlib"))
			{
				$do_gzip = 1;
			}
		}
		if ($do_gzip)
		{
			@ob_start();
			@ob_implicit_flush(0);
			header("Content-disposition: attachment; filename=$filename.sql.gz");
		}
		else
		{
			header("Content-disposition: attachment; filename=$filename.sql");
		}
		header('Content-type: unknown/unknown');
		$sql_header = $this->export_header();
		echo $sql_header;
		if ($tbl_name == '')
		{
			$tmp_tbl = $DB->get_table_names();
			foreach($tmp_tbl as $tbl)
			{
				if ($this->onlymolyx)
				{
					if (preg_match('/^' . TABLE_PREFIX . '/', $tbl))
					{
						$this->get_table_sql($tbl);
					}
				}
				else
				{
					$this->get_table_sql($tbl);
				}
				$this->dbsql = "\n";
			}
		}
		else
		{
			$this->get_table_sql($tbl_name);
		}
		if ($do_gzip)
		{
			$size = ob_get_length();
			$crc = crc32(ob_get_contents());
			$contents = gzcompress(ob_get_contents());
			ob_end_clean();
			echo "\x1f\x8b\x08\x00\x00\x00\x00\x00"
			 . substr($contents, 0, strlen($contents) - 4)
			 . $this->gzip_four_chars($crc)
			 . $this->gzip_four_chars($size);
		}
		exit();
	}

	function get_table_sql($tbl)
	{
		global $forums, $DB, $_INPUT;
		if ($this->createtable)
		{
			if (isset($this->noshow) AND !$this->noshow)
			{
				$this->dbsql .= "\n\n\n# --------------------------------------------------------\n\n";
				$this->dbsql .= "#\n";
				$this->dbsql .= "#     Export table '" . $tbl . "';\n";
				$this->dbsql .= "#\n\n";
			}

			if ($this->droptable)
			{
				if (empty($this->offset) || $this->offset == 0)
				{
					$this->dbsql .= "DROP TABLE IF EXISTS `$tbl`;\n";
				}
			}

			$result = $DB->query("SHOW CREATE TABLE `$tbl`");
			$ctable = $DB->fetch_array($result);
			$ctable = $ctable['Create Table'];
			if ($this->advbackup)
			{
				if (empty($this->offset) || $this->offset == 0)
				{
					$this->dbsql .= $ctable . ";\n\n";
				}
			}
			else
			{
				echo $this->dbsql;
				echo $ctable . ";\n\n";
			}
			$DB->free_result($result);
		}

		if ($this->skip == 1)
		{
			if (in_array($tbl, array(TABLE_PREFIX . 'adminsession', TABLE_PREFIX . 'session', TABLE_PREFIX . 'antispam', TABLE_PREFIX . 'search')))
			{
				return;
			}
		}

		$sql_array['SELECT'] = '*';
		$sql_array['FROM'] = $tbl;
		$limit = 500;
		if ($this->advbackup && $this->dbblocksize)
		{
			$sql_array['LIMIT'] = array($this->offset, $limit);
		}
		$querys = $DB->select($sql_array);

		$row_num = $DB->num_rows($querys);
		if ($row_num < 1)
		{
			$DB->free_result($querys);
			return;
		}
		else if ($row_num < $limit)
		{
			$this->noshow = true;
		}
		$db_key = '';
		$fields = $DB->get_result_fields($querys);
		$cnt = count($fields);
		for($i = 0; $i < $cnt; $i++)
		{
			$db_key .= '`' . $fields[$i]->name . '`, ';
		}
		$db_key = substr($db_key, 0, -2);
		$row_lines = 0;

		while ($row = $DB->fetch_array($querys))
		{
			$row_lines++;
			$db_value = '';
			for($i = 0; $i < $cnt; $i++)
			{
				if (!isset($row[$fields[$i]->name]))
				{
					$db_value .= 'NULL,';
				}
				else if ($row[$fields[$i]->name] != '')
				{
					if ($this->hex_type && in_array($fields[$i]->type, array('string', 'blob')))
					{
						$db_value .= '0x' . bin2hex($row[$fields[$i]->name]) . ',';
					}
					else
					{
						$db_value .= "'" . $this->sql_add_slashes($row[$fields[$i]->name]) . "',";
					}
				}
				else
				{
					$db_value .= "'',";
				}
			}
			$db_value = substr($db_value, 0, -1);
			if ($this->advbackup)
			{
				$this->dbsql .= "INSERT INTO `$tbl` ($db_key) VALUES($db_value);\n";
				$this->offset++;
				if ($this->dbblocksize)
				{
					if (strlen($this->dbsql) > ($this->dbblocksize * 1024))
					{
						$this->to_write();
					}
				}
			}
			else
			{
				echo "INSERT INTO `$tbl` ($db_key) VALUES($db_value);\n";
			}
		}

		if ($this->dbblocksize)
		{
			$DB->free_result($querys);
			if (strlen($this->dbsql) > ($this->dbblocksize * 1024))
			{
				$this->to_write();
			}
			else
			{
				$this->get_table_sql($tbl);
			}
			$this->noshow = true;
		}
	}

	function doadvbackup()
	{
		global $DB, $forums, $_INPUT;
		$this->dbblocksize = intval($_INPUT['dbblocksize']);
		$this->tableid = isset($_INPUT['tableid']) ? intval($_INPUT['tableid']) : 0;
		$this->offset = isset($_INPUT['offset']) ? intval($_INPUT['offset']) : 0;
		$this->dbexportfolder = preg_replace('/[^a-zA-Z0-9\-_]/', '', $_INPUT['dbexportfolder']);

		if ($this->dbexportfolder == '')
		{
			$forums->main_msg = $forums->lang['requirefoldername'];
			$this->backup_form();
		}

		if ($this->dbblocksize < 50 AND $this->dbblocksize != 0)
		{
			$forums->main_msg = $forums->lang['sqlfiletoosmall'];
			$this->backup_form();
		}

		$tmp_tbl = $DB->get_table_names();
		$t = 0;
		$this->step = (isset($_INPUT['step']) && $_INPUT['step']) ? intval($_INPUT['step']) : 1;
		foreach ($tmp_tbl as $tbl)
		{
			$this->noshow = false;
			if (($this->onlymolyx && preg_match('/^' . TABLE_PREFIX . '/', $tbl)) || !$this->onlymolyx)
			{
				$this->next_tableid++;
				if ($this->next_tableid < $this->tableid)
				{
					continue;
				}
				else
				{
					$this->get_table_sql($tbl);
				}
				$this->offset = 0;
			}
		}
		$this->write_to_file($this->dbsql, 1);
		$forums->admin->redirect('mysql.php?do=backup', $forums->lang['managemysql'], $forums->lang['sqlfilesavefinished']);
	}

	function to_write()
	{
		global $forums, $_INPUT;
		$this->write_to_file($this->dbsql);
		$forums->lang['sqlfilesaved'] = sprintf($forums->lang['sqlfilesaved'], intval($_INPUT['step']) ? intval($_INPUT['step']) - 1 : 0);
		$forums->admin->redirect("mysql.php?do=dobackup&amp;step={$this->step}&amp;advbackup=1&amp;offset={$this->offset}&amp;dbblocksize={$this->dbblocksize}&amp;tableid={$this->next_tableid}&amp;savedate={$this->savedate}&amp;onlymolyx={$this->onlymolyx}&amp;check={$this->md5_check}&amp;skip={$this->skip}&amp;createtable={$this->createtable}&amp;dbexportfolder={$this->dbexportfolder}&amp;hex_type={$this->hex_type}&amp;droptable={$this->droptable}", $forums->lang['managemysql'], $forums->lang['sqlfilesaved']);
		exit;
	}

	function write_to_file($data, $finish = '0')
	{
		global $forums, $bboptions, $_INPUT;
		$savefolder = $_INPUT['dbexportfolder'] ? $_INPUT['dbexportfolder'] : $this->savedate;
		if (is_writeable(ROOT_PATH . 'data/dbbackup'))
		{
			if (SAFE_MODE)
			{
				$filename = ROOT_PATH . 'data/dbbackup/' . $savefolder . '_' . $this->md5_check . '_' . $this->step . '.sql';
				if (file_exists($filename))
				{
					if (!is_writeable($filename))
					{
						$forums->main_msg = $filename . $forums->lang['filecannotwrite'];
						$this->backup_form();
					}
				}
			}
			else
			{
				$dir = ROOT_PATH . 'data/dbbackup/' . $savefolder . '/';
				$filename = $dir . $this->md5_check . '_' . $this->step . '.sql';
				if (checkdir($dir, 1) === false)
				{
					$forums->main_msg = $dir . $forums->lang['cannotcreate'];
					$this->backup_form();
				}

				if ($this->step == 1)
				{
					$forums->admin->rm_dir($dir, false);
				}

				if (file_exists($filename))
				{
					if (!is_writeable($filename))
					{
						$forums->main_msg = $filename . $forums->lang['filecannotwrite'];
						$this->backup_form();
					}
				}
			}
		}
		else
		{
			$forums->main_msg = 'data/dbbackup ' . $forums->lang['cannotwrite'];
			$this->backup_form();
		}
		$dumptype = $this->dbblocksize ? 'Multi Volume Backup' : 'Standard Backup';
		$vol = ($this->step == 1 && $finish) ? 'NULL' : 'vol_' . $this->step;
		$sql_header = $this->export_header($dumptype, $vol, $this->step, $finish, strlen($this->dbsql));
		$this->dbsql = $sql_header . $this->dbsql;
		if (false !== file_write($filename, $this->dbsql))
		{
			$this->step++;
			return;
		}
	}

	function sql_add_slashes($data)
	{
		return str_replace(array('\\', '\'', "\r", "\n"), array('\\\\', '\\\'', '\r', '\n'), $data);
	}

	function confirmbackup()
	{
		global $forums, $DB, $_INPUT;
		$pagetitle = 'MySQL ' . $DB->version . ' ' . $forums->lang['mysqlbackup'];
		$detail = $forums->lang['mysqlbackupdesc'];
		$forums->admin->nav[] = array('mysql.php?do=backup', $forums->lang['mysqlbackup']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		if ($this->mysql_version < 32321)
		{
			$forums->admin->print_cp_error($forums->lang['mysqlversiontooold']);
		}
		if ($_INPUT['advbackup'])
		{
			$type = $forums->lang['sqladvancedmode'];
			$_INPUT['droptable'] = intval($_INPUT['droptable']);
			$_INPUT['createtable'] = intval($_INPUT['createtable']);
			if (is_dir(ROOT_PATH . 'data/dbbackup/' . $_INPUT['dbexportfolder']))
			{
				$extra = '<br /><br /><strong>' . $forums->lang['backupfolderexist'] . '</strong><br /><br />';
			}
		}
		else
		{
			$type = $forums->lang['sqlnormalmode'];
		}
		$forums->admin->print_form_header(array(
			array('do', 'dobackup'),
			array('droptable', $_INPUT['droptable']),
			array('createtable', $_INPUT['createtable']),
			array('skip', $_INPUT['skip']),
			array('enablegzip', $_INPUT['enablegzip']),
			array('advbackup', $_INPUT['advbackup']),
			array('dbblocksize', $_INPUT['dbblocksize']),
			array('dbexportfolder', $_INPUT['dbexportfolder']),
			array('onlymolyx', $_INPUT['onlymolyx']),
			array('hex_type', $_INPUT['hex_type']),
		) , 'dobackform');
		$forums->admin->print_table_start($forums->lang['mysqlbackup'] . ' - ' . $type);
		$forums->admin->print_cells_single_row($forums->lang['sqlbackupinfo'] . $extra);
		$forums->admin->print_form_submit($forums->lang['startbackup']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function backup_form()
	{
		global $forums, $DB;
		$pagetitle = 'MySQL ' . $DB->version . ' ' . $forums->lang['mysqlbackup'];
		$detail = $forums->lang['mysqlbackupdesc'];
		$forums->admin->nav[] = array('mysql.php?do=backup', $forums->lang['mysqlbackup']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		if ($this->mysql_version < 32321)
		{
			$forums->admin->print_cp_error($forums->lang['mysqlversiontooold']);
		}
		$forums->admin->columns[] = array('&nbsp;', '60%');
		$forums->admin->columns[] = array('&nbsp;', '40%');
		$forums->admin->print_form_header(array(1 => array('do' , 'confirmbackup')), 'confirmform');
		$forums->admin->print_table_start($forums->lang['sqlnormalmodeinfo']);
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['addcreatepart'] . '</strong>',
			$forums->admin->print_yes_no_row('createtable', 1),
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['adddroppart'] . '</strong><div class="description">' . $forums->lang['adddroppartdesc'] . '</div>',
			$forums->admin->print_yes_no_row('droptable', 1),
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['skiptrashdata'] . '</strong><div class="description">' . $forums->lang['skiptrashdatadesc'] . '</div>',
			$forums->admin->print_yes_no_row('skip', 1),
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['backupmxdata'] . '</strong><div class="description">' . $forums->lang['backupmxdatadesc'] . '</div>',
			$forums->admin->print_yes_no_row('onlymolyx', 1),
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['hex_type'] . '</strong><div class="description">' . $forums->lang['hex_type_desc'] . '</div>',
			$forums->admin->print_yes_no_row('hex_type', 0),
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['enablegzip'] . '</strong><div class="description">' . $forums->lang['enablegzipdesc'] . '</div>',
			$forums->admin->print_yes_no_row('enablegzip', 1),
		));
		$forums->admin->print_table_footer();

		$forums->admin->columns[] = array('&nbsp;', '60%');
		$forums->admin->columns[] = array('&nbsp;', '40%');
		$forums->admin->print_table_start($forums->lang['advancedmode']);
		$forums->lang['advancedbackupdesc'] = sprintf($forums->lang['advancedbackupdesc'], $this->savedate);
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['advancedbackup'] . '</strong><div class="description">' . $forums->lang['advancedbackupdesc'] . '</div>',
			$forums->admin->print_yes_no_row('advbackup', 0),
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['advancedbktype'] . '</strong><div class="description">' . $forums->lang['advancedbktypedesc'] . '</div>',
			$forums->admin->print_input_row('dbblocksize', 0),
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['advancedbkfolder'] . '</strong><div class="description">' . $forums->lang['advancedbkfolderdesc'] . '</div>',
			$forums->admin->print_input_row('dbexportfolder', $this->savedate),
		));
		$forums->admin->print_form_submit($forums->lang['startbackup']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function restore_form()
	{
		global $forums, $DB, $_INPUT;
		$_INPUT['fromserver'] = isset($_INPUT['fromserver']) ? intval($_INPUT['fromserver']) : '';
		$pagetitle = 'MySQL ' . $DB->version . ' ' . $forums->lang['mysqlrestore'];
		$detail = $forums->lang['mysqlrestoredesc'];
		$forums->admin->nav[] = array('mysql.php?do=restore', $forums->lang['mysqlrestore']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array('&nbsp;', '60%');
		$forums->admin->columns[] = array('&nbsp;', '40%');
		$forums->admin->print_form_header(array(1 => array('do' , 'confirmrestore')), 'confirmreform', 'enctype="multipart/form-data"');
		$forums->admin->print_table_start($forums->lang['importsqlfile']);
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['importlocalsqlfile'] . '</strong><div class="description">' . $forums->lang['importlocalsqlfiledesc'] . '</div>',
			'<input type="file" value="' . $_INPUT['fromlocal'] . '" class="button" name="fromlocal" size="30" />',
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['importserversqlfile'] . '</strong><div class="description">' . $forums->lang['importserversqlfiledesc'] . '</div>',
			$forums->admin->print_yes_no_row('fromserver', $_INPUT['fromserver']),
		));
		$forums->admin->print_cells_single_row('<input type="submit" value=" ' . $forums->lang['importsqldata'] . ' " class="button" accesskey="s" />', 'center', 'pformstrip');
		$forums->admin->print_table_footer();
		$forums->admin->columns[] = array($forums->lang['sqlselected'], '');
		$forums->admin->columns[] = array($forums->lang['sqlinfolder'], '20%');
		$forums->admin->columns[] = array($forums->lang['sqlsavedate'], '20%');
		$forums->admin->columns[] = array($forums->lang['sqlbackuptype'], '20%');
		$forums->admin->columns[] = array($forums->lang['sqlfilesize'], '10%');
		$forums->admin->columns[] = array($forums->lang['sqlfilenums'], '10%');
		$forums->admin->columns[] = array($forums->lang['option'], '20%');
		$forums->admin->print_table_start($forums->lang['serversqlfilelist']);
		$dh = opendir(ROOT_PATH . 'data/dbbackup');
		while (false !== ($file = readdir($dh)))
		{
			if ($file != '.' && $file != '..')
			{
				if (is_dir(ROOT_PATH . 'data/dbbackup/' . $file))
				{
					$sqldirs['dir'][] = $file;
				}
				else
				{
					if (strrchr($file, '.') == '.sql')
					{
						$sqldirs['file'][] = $file;
					}
					else
					{
						@unlink(ROOT_PATH . 'data/dbbackup/' . $file);
					}
				}
			}
		}
		closedir($dh);
		if (is_array($sqldirs))
		{
			if (is_array($sqldirs['file']))
			{
				$key = '';
				foreach ($sqldirs['file'] as $files)
				{
					if (!preg_match('/^' . $key . '_(\w){32}_(\d+)\.sql$/', $files))
					{
						$key = preg_replace('/^(\d+)_(\w){32}_(\d+)\.sql$/', '\\1\\2', $files);
					}
					$groupdirs[$key][] = $files;
				}
				$sqldirs['dir'] = array_merge($groupdirs, $sqldirs['dir']);
			}
			foreach ($sqldirs['dir'] as $key => $dir)
			{
				if (is_array($dir))
				{
					$file_nums = 1;
					$files = array();
					$thisfiles = array();
					$info = array();
					$filesize = 0;
					natcasesort($dir);
					foreach ($dir as $file)
					{
						if ($fp = @fopen(ROOT_PATH . 'data/dbbackup/' . $file, 'rb'))
						{
							$size = filesize(ROOT_PATH . 'data/dbbackup/' . $file);
							$filesize += $size;
							$info = explode(',', base64_decode(preg_replace('/^# key:\s*(\w+).*/s', '\\1', fgets($fp, 256))));
							$thisfiles[] = $file;
							if ($info['2'] != 'NULL' && intval($info['3']) < $file_nums)
							{
								@fclose($fp);
								continue;
							}
							@fclose($fp);
						}
						$file_nums++;
					}
					if ($info['3'] != count($dir))
					{
						$files = array('errors' => 1);
					}
					else
					{
						$files = array('files' => $thisfiles, 'info' => $info , 'filesize' => $filesize);
						$dir = preg_replace('/^(\d+)_(\w){32}_(\d+)\.sql$/', '\\1', $files['files'][0]);
					}
				}
				else
				{
					$files = $this->get_folder_contents($dir);
				}
				$count = intval(count($files['files']));
				if ($files['errors'])
				{
					$forums->admin->print_cells_single_row($forums->lang['sqlfilecannotrestore']);
				}
				else
				{
					$forums->admin->print_cells_row(array(
						"<input type='radio' name='selectid' value='$dir' />",
						$dir,
						'<center>' . $files['info'][0] . '</center>',
						'<center>' . $files['info'][1] . '</center>',
						'<center>' . fetch_number_format($files['filesize'], true) . '</center>',
						'<center>' . $count . '</center>',
						'<center><a href="mysql.php?' . $forums->sessionurl . 'do=delsql&amp;id=' . $dir . '">' . $forums->lang['delete'] . '</a></center>',
					));
				}
			}
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function pttable_form()
	{
		global $forums, $DB, $_INPUT;

		$pagetitle = $forums->lang['databasepttable'];
		$detail = $forums->lang['databasepttabledesc'];
		$forums->admin->nav[] = array('mysql.php?do=pttable', $forums->lang['databasepttable']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array('&nbsp;', '100%');
		$forums->admin->print_form_header(array(1 => array('do' , 'createpttable')), 'pttableform');
		$forums->admin->print_cells_row(array('<center>'.$forums->lang['createposttabledesc'].'</center>'));
		$forums->admin->print_cells_single_row('<input type="submit" value="'.$forums->lang['createposttable'].'" class="button" accesskey="s" />', 'center', 'createpform');
		$forums->admin->print_form_end();
		$forums->admin->print_table_footer();

		$forums->admin->columns[] = array($forums->lang['pttablename'], '12%');
		$forums->admin->columns[] = array($forums->lang['pttablenumber'], '17%');
		$forums->admin->columns[] = array($forums->lang['pttablesize'], '17%');
		$forums->admin->columns[] = array($forums->lang['pttablepidrange'], '22%');
		$forums->admin->columns[] = array($forums->lang['pttablecreatetime'], '22%');
		$forums->admin->columns[] = array($forums->lang['currentposttbl'], '10%');
		$forums->admin->print_table_start($forums->lang['pttableinfo']);
		$forums->admin->print_form_header(array(1 => array('do' , 'changepttable')), 'pttableform');
		$tables = array();
		$rs = $DB->query('SHOW TABLE STATUS FROM `' . $DB->database . '`');
		while ($r = $DB->fetch_array($rs))
		{
			if (!preg_match('/^' . TABLE_PREFIX . 'post/', $r['Name']))
			{
				continue;
			}
			$tBit = ($r['Data_length'] > 0) ? 1 : 0;
			$tbl = $this->gen_size($r['Data_length'] , 3, $tBit);
			$tables[$r['Name']]['tbl'] = $tbl;
		}

		$result = $DB->query("SELECT * FROM " . TABLE_PREFIX . "splittable");
		while ($row = $DB->fetch_array($result))
		{
			$desc = '&nbsp;';
			if ($row['isdefaulttable'])
			{
				$desc = "<font color='red'>".$forums->lang['currenttable']."</font>";
			}
			elseif ($row['isempty'])
			{
				$desc = "<input type='radio' name='tblname' value='{$row['name']}'/>";
			}
			$tablename = TABLE_PREFIX . $row['name'];
			$data = date('Y-m-d H:i:s', $row['dateline']);
			$records = $DB->query_first("SELECT count(*) as num FROM $tablename");
			$forums->admin->print_cells_row(array(
				'<center>' . $tablename . '</center>',
				'<center>' . $records['num'] . '</center>',
				'<center>' . $tables[$tablename]['tbl'] . '</center>',
				'<center>' . $row['minpid'] . '-' . $row['maxpid'] . '</center>',
				'<center>' . $data . '</center>',
				'<center>' . $desc . '</center>',
				));
		}
		$forums->admin->print_cells_single_row('<input type="submit" value="'.$forums->lang['updatecurposttbl'].'" class="button" accesskey="s" />', 'center', 'updatepform');
		$forums->admin->print_form_end();
		$forums->admin->print_table_footer();
		$forums->admin->print_table_start($forums->lang['movepostdatastep']);

		$show_explain  = "<ul><li>".$forums->lang['movepostdatastep1']."</li></ul>";
		$show_explain .= "<ul><li>".$forums->lang['movepostdatastep2']."</li></ul>";
		$show_explain .= "<ul><li>".$forums->lang['movepostdatastep3']."</li></ul>";
		$show_explain .= "<ul><li>".$forums->lang['movepostdatastep4']."</li></ul>";
		$show_explain .= "<ul><li>".$forums->lang['movepostdatastep5']."</li></ul>";
		$forums->admin->print_cells_single_row($show_explain, 'left');
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function movedata_form()
	{
		global $forums, $DB, $_INPUT;

		$pagetitle = $forums->lang['databasemovedata'];
		$forums->admin->nav[] = array('mysql.php?do=movedata', $forums->lang['databasemovedata']);
		$forums->admin->print_cp_header($pagetitle);
		$forums->admin->columns[] = array('&nbsp;', '40%');
		$forums->admin->columns[] = array('&nbsp;', '60%');
		$forums->admin->print_form_header(array(1 => array('do' , 'domovedata')), 'movedataform');
		$forums->admin->print_table_start($forums->lang['databasemovedata']);

		$splittable = array();
		$result = $DB->query('SELECT * FROM ' . TABLE_PREFIX . "splittable");
		while ($row = $DB->fetch_array($result))
		{
			$splittable[$row['id']] = TABLE_PREFIX . $row['name'];
		}
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['movefrompostdata'] . '</strong>',
			$forums->admin->print_input_select_row("fromtable", $splittable)
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['movetopostdata'] . '</strong>',
			$forums->admin->print_input_select_row("totable", $splittable)
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['moveposttidrange'] . '</strong><div class="description">' . $forums->lang['moveposttidrangedesc'] . '</div>',
			$forums->admin->print_input_row('mintid','','text','','8') . ' < ' . $forums->admin->print_input_row('maxtid','','text','','8'),
		));
		$forums->admin->print_cells_row(array(
			'<strong>' . $forums->lang['movepostdatathreadnum'] . '</strong>',
			$forums->admin->print_input_row('percycle','100','text','','8')
		));

		$forums->admin->print_cells_single_row('<input type="submit" value=" ' . $forums->lang['ok'] . ' " class="button" accesskey="s" />', 'center', 'pformstrip');
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();

		$forums->admin->print_table_start($forums->lang['movepoststep']);

		$show_explain  = "<ul><li>".$forums->lang['movepoststep1']."</li></ul>";
		$show_explain .= "<ul><li>".$forums->lang['movepoststep2']."</li></ul>";
		$show_explain .= "<ul><li>".$forums->lang['movepoststep3']."</li></ul>";
		$show_explain .= "<ul><li>".$forums->lang['movepoststep4']."</li></ul>";
		$show_explain .= "<ul><li>".$forums->lang['movepoststep5']."</li></ul>";
		$show_explain .= "<ul><li>".$forums->lang['movepoststep6']."</li></ul>";
		$show_explain .= "<ul><li>".$forums->lang['movepoststep7']."</li></ul>";
		$forums->admin->print_cells_single_row($show_explain, 'left');
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function get_folder_contents($folder)
	{
		$files = array();
		$info = array();
		$filesize = 0;
		$dh = @opendir(ROOT_PATH . 'data/dbbackup/' . $folder);
		$file_nums = 1;
		while (false !== ($file = @readdir($dh)))
		{
			if ($file != '.' && $file != '..')
			{
				$allfolder[] = $file;
			}
		}
		if (is_array($allfolder))
		{
			natcasesort($allfolder);
			foreach ($allfolder AS $file)
			{
				if ($fp = @fopen(ROOT_PATH . 'data/dbbackup/' . $folder . '/' . $file, 'rb'))
				{
					$size = filesize(ROOT_PATH . 'data/dbbackup/' . $folder . '/' . $file);
					$filesize += $size;
					$info = explode(',', base64_decode(preg_replace('/^# key:\s*(\w+).*/s', '\\1', fgets($fp, 256))));
					$files[] = $file;
					if ($info[2] != 'NULL' && intval($info[3]) < $file_nums)
					{
						@fclose($fp);
						continue;
					}
					@fclose($fp);
				}
				$file_nums++;
			}
		}
		@closedir($dh);
		if ($info[3] != count($allfolder))
		{
			return array('errors' => 1);
		}
		return array('files' => $files, 'info' => $info , 'filesize' => $filesize);
	}

	function dodeletesql()
	{
		global $forums, $_INPUT;
		$delfolder = trim($_INPUT['id']);
		if (SAFE_MODE)
		{
			$dh = opendir(ROOT_PATH . 'data/dbbackup');
			while ($file = readdir($dh))
			{
				if ($file != '.' && $file != '..')
				{
					if (preg_match('/^' . $delfolder . '_(\w){32}_(\d+)\.sql$/', $file))
					{
						@unlink(ROOT_PATH . 'data/dbbackup/' . $file);
					}
				}
			}
			closedir($dh);
		}
		else
		{
			$forums->admin->rm_dir(ROOT_PATH . 'data/dbbackup/' . $delfolder);
			@rmdir($delfolder);
		}
		$forums->main_msg = $forums->lang['backupfolderdeleted'];
		$this->restore_form();
	}

	function confirmrestore()
	{
		global $forums, $_INPUT, $DB;
		if (!$_FILES['fromlocal']['name'] && (!$_INPUT['fromserver'] || ($_INPUT['fromserver'] && !$_INPUT['selectid'])))
		{
			$forums->main_msg = $forums->lang['norestorefiles'];
			$this->restore_form();
		}

		$filesize = 0;
		$rtype = $_FILES['fromlocal']['name'] ? 1 : 0;
		if ($_FILES['fromlocal']['name'])
		{
			$datafile = TIMENOW . '.tmp';
			$extension = strtolower(strrchr($_FILES['fromlocal']['name'], '.'));
			if ($extension == 'gz')
			{
				$forums->main_msg = $forums->lang['cannotextractgzfile'];
				$this->restore_form();
			}
			$filesize = $_FILES['fromlocal']['size'];
			$type = $forums->lang['local'];

			if (!@move_uploaded_file($_FILES['fromlocal']['tmp_name'], ROOT_PATH . 'data/dbbackup/' . $datafile))
			{
				if (!@copy($_FILES['fromlocal']['tmp_name'], ROOT_PATH . 'data/dbbackup/' . $datafile))
				{
					$forums->main_msg = $forums->lang['cannotimportfile'];
					$this->restore_form();
				}
			}
			$filestuff = @file_get_contents(ROOT_PATH . 'data/dbbackup/' . $datafile);
			$info = explode(',', base64_decode(preg_replace("/^# key:\s*(\w+).*/s", "\\1", $filestuff)));
			if ($info[2] == 'NULL')
			{
				$file_nums = 1;
			}
			else
			{
				$file_nums = $forums->lang['localunknown'];
				$extra = "<br /><br />" . $forums->lang['cannotbatchimport'];
			}
		}
		else
		{
			$type = $forums->lang['server'];
			if (SAFE_MODE)
			{
				$dh = opendir(ROOT_PATH . 'data/dbbackup');
				while (false !== ($file = readdir($dh)))
				{
					if ($file != '.' && $file != '..')
					{
						$allfolder[] = $file;
					}
				}
				if (is_array($allfolder))
				{
					natcasesort($allfolder);
					foreach ($allfolder AS $file)
					{
						if (preg_match('/^' . $_INPUT['selectid'] . '_(\w){32}_(\d+)\.sql$/', $file))
						{
							if ($fp = @fopen(ROOT_PATH . 'data/dbbackup/' . $file, 'rb'))
							{
								$filesize += filesize(ROOT_PATH . 'data/dbbackup/' . $file);
								$info = explode(",", base64_decode(preg_replace('/^# key:\s*(\w+).*/s', '\\1', fgets($fp, 256))));
								if ($file_nums == 0)
								{
									$datafile = $file;
								}
								$files[] = $file;
								@fclose($fp);
							}
							$file_nums++;
						}
					}
				}
				closedir($dh);
			}
			else
			{
				$datapath = ROOT_PATH . 'data/dbbackup/' . $_INPUT['selectid'];
				$dh = opendir($datapath);
				$file_nums = 0;
				while (false !== ($file = readdir($dh)))
				{
					if ($file != '.' && $file != '..')
					{
						$allfolder[] = $file;
					}
				}
				if (is_array($allfolder))
				{
					natcasesort($allfolder);
					foreach ($allfolder as $file)
					{
						if ($fp = @fopen($datapath . '/' . $file, 'rb'))
						{
							$filesize += filesize($datapath . '/' . $file);
							$info = explode(',', base64_decode(preg_replace('/^# key:\s*(\w+).*/s', '\\1', fgets($fp, 256))));
							if ($file_nums == 0)
							{
								$datafile = $file;
							}
							$files[] = $file;
							@fclose($fp);
						}
						$file_nums++;
					}
				}
				closedir($dh);
			}
		}
		$pagetitle = 'MySQL ' . $DB->version . ' ' . $forums->lang['mysqlrestore'];
		$detail = $forums->lang['mysqlrestoredesc'];
		$forums->admin->nav[] = array('mysql.php?do=restore', $forums->lang['mysqlrestore']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$forums->admin->columns[] = array('' , '40%');
		$forums->admin->columns[] = array('', '60%');
		$forums->admin->print_form_header(array(
			array('do', 'dorestore'),
			array('type', $rtype),
			array('file', urlencode($datafile)),
			array('filepath', $_INPUT['selectid']),
		), 'restoreform', 'enctype="multipart/form-data"');
		$forums->admin->print_table_start($forums->lang['mysqlrestore'] . ' - ' . $type);
		$forums->admin->print_cells_single_row($forums->lang['confirmrestore'] . $extra);
		$forums->admin->print_cells_row(array($forums->lang['restoresqlfilesize'] . ':', $filesize,));
		$forums->admin->print_cells_row(array($forums->lang['sqlfilenums'] . ':', $file_nums,));
		$forums->admin->print_cells_row(array($forums->lang['sqlbackuptype'] . ':', $info[1],));
		$forums->admin->print_cells_row(array($forums->lang['sqlsavedate'] . ':', $info[0],));
		$forums->admin->print_form_submit($forums->lang['restoresql']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function dorestore()
	{
		global $forums, $DB, $_INPUT;
		$type = (isset($_INPUT['type']) && $_INPUT['type']) ? 1 : 0;
		$pp = (isset($_INPUT['pp']) && $_INPUT['pp']) ? intval($_INPUT['pp']) : 1;
		$file = trim(rawurldecode($_INPUT['file']));
		$filepath = trim($_INPUT['filepath']);
		if ($type || SAFE_MODE)
		{
			$urlfile = ROOT_PATH . 'data/dbbackup/' . $file;
		}
		else
		{
			$urlfile = ROOT_PATH . 'data/dbbackup/' . $filepath . '/' . $file;
		}

		if (!$fp = @fopen(ROOT_PATH . 'data/dbbackup/unlock.dbb', 'w'))
		{
			$forums->main_msg = $forums->lang['cannotlock'];
			$this->restore_form();
		}
		@fclose($fp);

		if ($fp = @fopen($urlfile, 'rb'))
		{
			$info = explode(',', base64_decode(preg_replace('/^# key:\s*(\w+).*/s', '\\1', fgets($fp, 256))));
			$filesize = @filesize($urlfile);
			$offset = isset($_INPUT['offset']) ? intval($_INPUT['offset']) : 0;
			if ($offset <= $filesize && fseek($fp, $offset) == 0)
			{
				$sql = '';
				$in_parents = false;
				$line_number = isset($_INPUT['begin']) ? intval($_INPUT['begin']) : 0;
				$end = $line_number + 3000;
				while ($line_number < $end || $sql != '')
				{
					$line = '';
					while (!feof($fp) && substr($line, -1) != "\n")
					{
						$line .= fgets($fp, 16384);
					}
					$line = str_replace(array("\r\n", "\r"), "\n", $line);

					if (!$in_parents)
					{
						if (!$in_parents && (trim($line) == '' || strpos ($line, '-- ') === 0 || strpos ($line, '#') === 0))
						{
							$line_number++;
							continue;
						}
					}
					$line_tmp = str_replace ('\\\\', '', $line);
					$parents = substr_count($line_tmp, "'") - substr_count($line_tmp, "\\'");
					if ($parents % 2 != 0)
					{
						$in_parents = !$in_parents;
					}
					$sql .= $line;

					if (substr(trim($line), -1) === ';' && !$in_parents)
					{
						$sql = trim($sql);
						if (strpos($sql, 'CREATE TABLE') === 0)
						{
							if ($this->mysql_version >= 40100)
							{
								$sql = str_replace('TYPE=HEAP', 'ENGINE=MEMORY', $sql);
								if (strpos($sql, 'DEFAULT CHARSET') === false)
								{
									$sql = substr($sql, 0, -1) . ' DEFAULT CHARSET=utf8;';
								}
							}
						}
						$DB->query_unbuffered($sql);
						$sql = '';
					}
					$line_number++;
				}
				$offset = ftell($fp);
			}
			$feof = feof($fp);
			@fclose($fp);

			if ($feof && $line_number == 0 && $offset == 0)
			{
				if ($info[2] == 'NULL' || $type == 1)
				{
					@unlink(ROOT_PATH . 'data/dbbackup/unlock.dbb');
					$forums->admin->redirect('mysql.php?do=backup', $forums->lang['managemysql'], $forums->lang['sqlfileimported']);
				}
				else if ($info[4])
				{
					@unlink(ROOT_PATH . 'data/dbbackup/unlock.dbb');
					$forums->admin->redirect('mysql.php?do=backup', $forums->lang['managemysql'], $forums->lang['allsqlfileimported']);
				}
			}

			$nextfile = $file;
			if ($feof)
			{
				if ($info[1] != 'Standard Backup')
				{
					$pp++;
					$nextfile =  SAFE_MODE ? preg_replace('/^(\d+)_(\w{32})_(\d+).sql$/', "\\1_\\2_$pp.sql", $file) : preg_replace('/^(\w{32})_(.+?).sql/', "\\1_$pp.sql" , $file);
				}

				$line_number = $offset = 0;
			}

			$forums->admin->redirect("mysql.php?do=dorestore&amp;type=$type&amp;filepath=$filepath&amp;file=" . urlencode($nextfile) . "&amp;pp=$pp&amp;begin=$line_number&amp;offset=$offset", $forums->lang['managemysql'], $forums->lang['importedsqlfile'] . " - $file (vol:" . ($pp - 1) . ") Line: $line_number");
		}
		else
		{
			$forums->main_msg = $forums->lang['cannotimportedfile'] . ' - ' . $file;
			$this->restore_form();
		}
	}

	function dopttable($type='create')
	{
		global $forums, $DB, $bboptions, $_INPUT;

		$splittable = array();
		$nextid = 0;
		$defaulttable = 'post';
		$rs = $DB->query("SELECT * FROM " . TABLE_PREFIX . "splittable");
		while ($row = $DB->fetch_array($rs))
		{
			$id = intval(str_replace('post', '', $row['name']));
			$nextid = $nextid < $id ? $id : $nextid;
			$splittable[$row['id']] = $row;
			if ($row['isdefaulttable'])
			{
				$defaulttable = $row['name'];
			}
		}
		if ($type=='create')
		{
			$nextid += 1;
			$tablename = "post" . $nextid;
			//判断表是否存在

			$DB->query("SHOW TABLES LIKE '" . TABLE_PREFIX . "$tablename'");
			if($DB->num_rows() > 0)
			{
				$forums->admin->print_cp_error(sprintf($forums->lang['createtableexist'], $tablename));
			}
			$result = $DB->query("SHOW CREATE TABLE " . TABLE_PREFIX . "post");
			while ($row=$DB->fetch_array($result))
			{
				$createsql = $row['Create Table'];
			}
			$createsql = str_replace(TABLE_PREFIX . "post", TABLE_PREFIX . $tablename, $createsql);
			//创建下一个post表,并向splittable插入一条数据
			$DB->query($createsql);
			$newtable = array(
				'name' => $tablename,
				'minpid' => 0,
				'maxpid' => 0,
				'isdefaulttable' => 0,
				'isempty' => 1,
				'dateline' => TIMENOW
				);
			$DB->insert(TABLE_PREFIX . 'splittable', $newtable);
			$msgcontent = $forums->lang['createtablesuccess'];
			$msgtitle = $forums->lang['databasemovedata'];
		}
		elseif ($type=='change')
		{
			if ($bboptions['bbactive'])
			{
				$forums->admin->print_cp_error($forums->lang['mustclosebbs']);
			}
			$tblname = trim($_INPUT['tblname']);
			if (!$tblname)
			{
				$forums->admin->print_cp_error($forums->lang['requireselectdeftable']);
			}
			$total = $DB->query_first("SELECT Max(pid) as maxpid, Min(pid) as minpid
				FROM " . TABLE_PREFIX . $defaulttable);

			$DB->update(TABLE_PREFIX . 'splittable', array('maxpid'=>intval($total['maxpid']), 'minpid'=>intval($total['minpid']), 'isempty'=>0, 'isdefaulttable'=>0), "name='$defaulttable'");

			$DB->update(TABLE_PREFIX . 'splittable', array('minpid'=>intval($total['maxpid'])+1, 'isempty'=>1, 'isdefaulttable'=>1, 'dateline' => TIMENOW), "name='$tblname'");

			$msgcontent = $forums->lang['changedefaulttable'];
			$msgtitle = $forums->lang['databasepttable'];
		}
		$forums->func->recache('splittable');
		$forums->admin->redirect("mysql.php?{$forums->sessionurl}&do=pttable", $msgtitle, $msgcontent);
	}

	function domovedata()
	{
		global $forums, $DB, $_INPUT;
		$done = 0;
		if ($bboptions['bbactive'])
		{
			$forums->admin->print_cp_error($forums->lang['mustclosebbs']);
		}
		$fromtable = $_INPUT['fromtable'] ? trim($_INPUT['fromtable']) : '';
		$totable = $_INPUT['totable'] ? trim($_INPUT['totable']) : '';
		$upfromtable = str_replace(TABLE_PREFIX, '', $fromtable);
		$uptotable = str_replace(TABLE_PREFIX, '', $totable);
		if (!$fromtable || !$totable)
		{
			$forums->admin->print_cp_error($forums->lang['selecttofromtable']);
		}
		if ($fromtable == $totable)
		{
			$forums->admin->print_cp_error($forums->lang['tofromtablediff']);
		}
		$DB->query("SHOW TABLES LIKE '" . TABLE_PREFIX . "$fromtable'");
		if($DB->num_rows() < 0)
		{
			$forums->admin->print_cp_error(sprintf($forums->lang['fromtablenotexist'], $fromtable));
		}
		$DB->query("SHOW TABLES LIKE '" . TABLE_PREFIX . "$totable'");
		if($DB->num_rows() < 0)
		{
			$forums->admin->print_cp_error(sprintf($forums->lang['totablenotexist'], $totable));
		}
		$table_fields = $DB->query('SHOW COLUMNS FROM ' . TABLE_PREFIX . 'post');
		$fields = array();
		while ($row = $DB->fetch_array($table_fields))
		{
			if ($row['Field'] == 'pid')
			{
				continue;
			}
			$fields[] = $row['Field'];
		}

		$mintid = $_INPUT['mintid'] ? intval($_INPUT['mintid']) : 0;
		$maxtid = $_INPUT['maxtid'] ? intval($_INPUT['maxtid']) : 0;
		if ($mintid == $maxtid)
		{
			$forums->admin->print_cp_error($forums->lang['movetidnotsame']);
		}
		if ($mintid > $maxtid)
		{
			$forums->admin->print_cp_error($forums->lang['movetidmaxminerror']);
		}

		$start = $_INPUT['pp'] ? intval($_INPUT['pp']) : ($mintid-1);
		$end = $_INPUT['percycle'] ? intval($_INPUT['percycle']) : 500;
		$end += $start;
		$end = $end > $maxtid ? $maxtid : $end;
		if (!$_INPUT['pp'])
		{
			$movenum = $end-$mintid+1;
		}
		else
		{
			$movenum = $end-$mintid;
		}
		if ($upfromtable == 'post')
		{
			$ext_cond = " AND (posttable='' OR posttable='post')";
		}
		else
		{
			$ext_cond = " AND posttable='$upfromtable'";
		}
		$result = $DB->query("SELECT tid, title FROM " . TABLE_PREFIX . "thread WHERE tid > $start AND tid <= $end" . $ext_cond);
		$output = $tids = array();
		while ($row = $DB->fetch_array($result))
		{
			$tid = intval($row['tid']);
			$tids[] = $tid;
			if ($_INPUT['percycle'] <= 200)
			{
				$output[] = $forums->lang['movedthreaddata'] . " - " . $row['title'];
			}
			$done++;
		}
		if ($tids)
		{
			$DB->query("INSERT INTO " . $totable . " (" . implode(',', $fields) . ") SELECT " . implode(',', $fields) . " FROM " . $fromtable . "  WHERE threadid IN (" . implode(",", $tids) . ")");
			$DB->update(TABLE_PREFIX . 'thread', array('posttable'=>$uptotable), $DB->sql_in('tid', $tids));
			$DB->delete($fromtable, $DB->sql_in('threadid', $tids));
		}
		if (!$done && $end == $maxtid)
		{
			$tors = $DB->query_first("SELECT count(*) as num, Max(pid) as maxpid, Min(pid) as minpid FROM $totable");
			$fromrs = $DB->query_first("SELECT count(*) as num, Max(pid) as maxpid, Min(pid) as minpid FROM $fromtable");
			$fromupdate = array('maxpid'=>$fromrs['maxpid'], 'minpid'=>$fromrs['minpid']);
			if ($fromrs['num']<=0)
			{
				$fromupdate = array_merge($fromupdate, array('isempty'=>1));
			}
			else
			{
				$fromupdate = array_merge($fromupdate, array('isempty'=>0));
			}
			$toupdate = array('maxpid'=>$tors['maxpid'], 'minpid'=>$tors['minpid']);
			if ($tors['num']<=0)
			{
				$toupdate = array_merge($toupdate, array('isempty'=>1));
			}
			else
			{
				$toupdate = array_merge($toupdate, array('isempty'=>0));
			}
			$DB->update(TABLE_PREFIX . 'splittable', $toupdate, "name='$uptotable'");
			$DB->update(TABLE_PREFIX . 'splittable', $fromupdate, "name='$upfromtable'");
			$forums->func->recache('splittable');
			$text = "<strong>" . $forums->lang['movedthreadsuccess'] . "</strong><br />" . implode("<br />", $output);
			$url = "mysql.php?{$forums->sessionurl}&amp;do=movedata";
			$time = 2;
		}
		else
		{
			$forums->lang['finishmovepostdata'] = sprintf($forums->lang['finishmovepostdata'], $movenum);
			$text = "<strong>" . $forums->lang['finishmovepostdata'] . "</strong><br />" . implode("<br />", $output);
			$url = "mysql.php?do=domovedata&amp;fromtable=" . $fromtable . "&amp;totable=" . $totable . "&amp;mintid=" . $mintid . "&amp;maxtid=" . $maxtid . "&amp;percycle=" . $_INPUT['percycle'] . '&amp;pp=' . $end;
			$time = 3;
		}

		$forums->admin->redirect($url, $forums->lang['databasemovedata'], $text, 0, $time);
	}

	function view_sql($sql)
	{
		global $forums, $DB, $_INPUT;
		$limit = 30;
		$start = intval($_INPUT['pp']) == '' ? 0 : intval($_INPUT['pp']);
		$pages = '';
		$pagetitle = 'MySQL ' . $DB->version . ' ' . $forums->lang['managemysql'];
		$detail = $forums->lang['managemysqldesc'];
		$forums->admin->nav[] = array('', $forums->lang['mysqlruninfo']);
		$forums->admin->print_cp_header($pagetitle, $detail);
		$map = array(
			'processes' => $forums->lang['mysqlprocesses'],
			'runtime' => $forums->lang['mysqlruntime'],
			'system' => $forums->lang['mysqlsystem'],
		);
		if ($map[$_INPUT['do']] != '')
		{
			$tbl_title = $map[$_INPUT['do']];
			$this_query = false;
		}
		else
		{
			$tbl_title = $forums->lang['manualquery'];
			$this_query = true;
		}
		if ($this_query)
		{
			$forums->admin->columns[] = array('&nbsp;' , '100%');
			$forums->admin->print_form_header(array(1 => array('do' , 'runsql'),), 'runsqlform');
			$forums->admin->print_table_start($forums->lang['doquery']);
			$forums->admin->print_cells_row(array('<center>' . $forums->admin->print_textarea_row('query', $sql) . '</center>'));
			$forums->admin->print_form_submit($forums->lang['doquery']);
			$forums->admin->print_table_footer();
			$forums->admin->print_form_end();
			if (preg_match('/^DROP|CREATE|FLUSH/i', trim($sql)))
			{
				$forums->admin->error = $forums->lang['querynorun'];
			}
		}
		$DB->return_die = 1;
		$result = $DB->query($sql);
		if ($DB->error != "")
		{
			$forums->admin->columns[] = array('&nbsp;' , '100%');
			$forums->admin->print_table_start($forums->lang['sqlerrors']);
			$forums->admin->print_cells_row(array($DB->error));
			$forums->admin->print_table_footer();
			$forums->admin->print_cp_footer();
		}
		if (preg_match('/^INSERT|UPDATE|DELETE|ALTER/i', trim($sql)))
		{
			$forums->admin->columns[] = array('&nbsp;' , '100%');
			$forums->admin->print_table_start($forums->lang['sqlquerydone']);
			$forums->lang['queryalreadyrun'] = sprintf($forums->lang['queryalreadyrun'], $sql);
			$forums->admin->print_cells_row(array($forums->lang['queryalreadyrun']));
			$forums->admin->print_table_footer();
			$forums->admin->print_cp_footer();
		}
		else if (preg_match('/^SELECT/i', $sql))
		{
			if (!preg_match('/LIMIT[ 0-9,]+$/i', $sql))
			{
				$rows_returned = $DB->num_rows();
				if ($rows_returned > $limit)
				{
					$links = $forums->func->build_pagelinks(array(
						'totalpages' => $rows_returned,
						'perpage' => $limit,
						'curpage' => $start,
						'pagelink' => "mysql.php?{$forums->sessionurl}do=runsql&amp;query=" . urlencode($sql),
					));
					$sql .= " LIMIT $start, $limit";
					$result = $DB->query($sql);
				}
			}
		}
		$fields = $DB->get_result_fields($result);
		$cnt = count($fields);
		for($i = 0; $i < $cnt; $i++)
		{
			$forums->admin->columns[] = array($fields[$i]->name , '');
		}
		$forums->admin->print_table_start($forums->lang['result'] . ': ' . $tbl_title);
		if ($links != '')
		{
			$forums->admin->print_cells_single_row($links, 'left', 'tdrow2');
		}
		while ($r = $DB->fetch_array($result))
		{
			$rows = array();
			for($i = 0; $i < $cnt; $i++)
			{
				if ($this_query == 1)
				{
					if (strlen($r[$fields[$i]->name]) > 200)
					{
						$r[$fields[$i]->name] = $forums->func->fetch_trimmed_title($r[$fields[$i]->name], 200);
					}
				}
				$rows[] = $r[$fields[$i]->name] ? wordwrap(utf8_htmlspecialchars(nl2br($r[$fields[$i]->name])) , 50, '<br />', 1) : '&nbsp;';
			}
			$forums->admin->print_cells_row($rows);
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_cp_footer();
	}

	function sqltool()
	{
		global $forums, $DB, $_INPUT;
		$tables = array();
		$tables = $_INPUT['table'];
		if (count($tables) < 1)
		{
			$forums->admin->print_cp_error($forums->lang['requireselecttables']);
		}
		if (strtoupper($_INPUT['tool']) == 'DROP' || strtoupper($_INPUT['tool']) == 'CREATE' || strtoupper($_INPUT['tool']) == 'FLUSH')
		{
			$forums->admin->print_cp_error($forums->lang['sqlerrors']);
		}
		$pagetitle = 'MySQL ' . $DB->version . ' ' . $forums->lang['sqltools'];
		$detail = $forums->lang['managemysqldesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		foreach($tables as $table)
		{
			$result = $DB->query(strtoupper($_INPUT['tool']) . " TABLE $table");
			$fields = $DB->get_result_fields($result);
			$data = $DB->fetch_array($result);
			$cnt = count($fields);
			for($i = 0; $i < $cnt; $i++)
			{
				$forums->admin->columns[] = array($fields[$i]->name , '');
			}
			$forums->admin->print_table_start($forums->lang['result'] . ': ' . $_INPUT['tool'] . ' ' . $table);
			$rows = array();
			for($i = 0; $i < $cnt; $i++)
			{
				$rows[] = $data[ $fields[$i]->name ];
			}
			$forums->admin->print_cells_row($rows);
			$forums->admin->print_table_footer();
		}
		$forums->admin->print_cp_footer();
	}

	function sqlmain()
	{
		global $forums, $DB;
		$form_array = array();
		if ($this->mysql_version < 32322)
		{
			$extra = '<br /><strong>' . $forums->lang['mysqlversionold'] . '</strong>';
		}
		$pagetitle = 'MySQL ' . $DB->version . ' ' . $forums->lang['sqltools'];
		$detail = $forums->lang['managemysqldesc'] . $extra;
		$forums->admin->print_cp_header($pagetitle, $detail);
		$idx_size = 0;
		$tbl_size = 0;
		$forums->admin->print_form_header(array(1 => array('do' , 'dotool'),) , "mutliact");
		if ($this->mysql_version >= 32303)
		{
			$forums->admin->columns[] = array($forums->lang['sqltables'], "20%");
			$forums->admin->columns[] = array($forums->lang['sqlrows'], "10%");
			$forums->admin->columns[] = array($forums->lang['sqlsize'], "20%");
			$forums->admin->columns[] = array($forums->lang['sqlindexsize'], "20%");
			$forums->admin->columns[] = array($forums->lang['export'], "10%");
			$forums->admin->columns[] = array('<input name="allbox" type="checkbox" value="' . $forums->lang['selectall'] . '" onClick="CheckAll(document.mutliact);" />' , '10%');
			$forums->admin->print_table_start($forums->lang['managesqltables']);
			$result = $DB->query('SHOW TABLE STATUS FROM `' . $DB->database . '`');
			while ($r = $DB->fetch_array($result))
			{
				if (!preg_match('/^' . TABLE_PREFIX . '/', $r['Name']))
				{
					continue;
				}
				$idx_size += $r['Index_length'];
				$tbl_size += $r['Data_length'];
				$iBit = ($r['Index_length'] > 0) ? 1 : 0;
				$tBit = ($r['Data_length'] > 0) ? 1 : 0;
				$idx = $this->gen_size($r['Index_length'], 3, $iBit);
				$tbl = $this->gen_size($r['Data_length'] , 3, $tBit);
				$forums->admin->print_cells_row(array("<strong><span style='font-size:12px'><a href='mysql.php?{$forums->sessionurl}do=runsql&amp;query=" . urlencode("SELECT * FROM {$r['Name']}") . "'>{$r['Name']}</a></span></strong>",
					'<center>' . $r['Rows'] . '</center>',
					'<div align="right">' . $tbl . '</div>',
					'<div align="right">' . $idx . '</div>',
					"<center><a href='mysql.php?{$forums->sessionurl}do=export_tbl&amp;tbl={$r['Name']}&amp;createtable=1&amp;droptable=1'>" . $forums->lang['export'] . "</a></center>",
					"<center><input name='table[]' value='{$r['Name']}' type='checkbox' /></center>",
				));
			}
			$total = $idx_size + $tbl_size;
			$iBit = ($idx_size > 0) ? 1 : 0;
			$tBit = ($tbl_size > 0) ? 1 : 0;
			$oBit = ($total > 0) ? 1 : 0;
			$idx = $this->gen_size($idx_size , 3, $iBit);
			$tbl = $this->gen_size($tbl_size , 3, $tBit);
			$tot = $this->gen_size($total , 3, $oBit);
			$forums->admin->print_cells_row(array (
				'&nbsp;',
				'&nbsp;',
				'<div align="right"><strong>' . $tbl . '</strong></div>',
				'<div align="right"><strong>' . $idx . '</strong></div>',
				array('<div align="right">' . $forums->lang['total'] . ' (<strong>' . $tot . '</strong>)</div>', 2),
			));
		}
		else
		{
			$forums->admin->columns[] = array($forums->lang['sqltables'], "60%");
			$forums->admin->columns[] = array($forums->lang['sqlrows'], "30%");
			$forums->admin->columns[] = array('<input name="allbox" type="checkbox" value="' . $forums->lang['selectall'] . '" onClick="CheckAll(document.mutliact);" />' , "10%");
			$forums->admin->print_table_start($forums->lang['managesqltables']);
			$tables = $DB->get_table_names();
			foreach($tables as $tbl)
			{
				if (strpos($tbl, TABLE_PREFIX) !== 0)
				{
					continue;
				}
				$cnt = $DB->query_first("SELECT COUNT(*) AS Rows FROM $tbl");
				$forums->admin->print_cells_row(array("<strong>$tbl</strong>",
					"<center>{$cnt['Rows']}</center>",
					"<center><input name='table[]' value='$tbl' type='checkbox' /></center>",
				));
			}
		}
		if ($this->mysql_version < 32322)
		{
			$forums->admin->print_cells_single_row('<select class="button" name="tool">
				<option value="optimize">' . $forums->lang['optimizesqltables'] . '</option>
				</select>
				<input type="submit" value="' . $forums->lang['ok'] . '" class="button" />', 'center', 'tdrow2');
		}
		else
		{
			$forums->admin->print_cells_single_row('<select class="button" name="tool">
				<option value="optimize">' . $forums->lang['optimizesqltables'] . '</option>
				<option value="repair">' . $forums->lang['repairsqltables'] . '</option>
				<option value="check">' . $forums->lang['checksqltables'] . '</option>
				<option value="analyze">' . $forums->lang['analyzesqltables'] . '</option>
				</select>
				<input type="submit" value="' . $forums->lang['ok'] . '" class="button" />', 'center', 'tdrow2');
		}
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_form_header(array(1 => array('do' , 'runsql'),), 'runsqlform');
		$forums->admin->columns[] = array($forums->lang['sqltables'], "30%");
		$forums->admin->columns[] = array($forums->lang['sqlrows'], "70%");
		$forums->admin->print_table_start($forums->lang['doquery']);
		$forums->admin->print_cells_row(array($forums->lang['domanualquery'], $forums->admin->print_textarea_row('query', '')));
		$forums->admin->print_form_submit($forums->lang['doquery']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function gen_size($val, $li, $sepa)
	{
		$sep = pow(10, $sepa);
		$li = pow(10, $li);
		$retval = $val;
		$unit = 'Bytes';
		if ($val >= $li * 1000000)
		{
			$val = round($val / (1073741824 / $sep)) / $sep;
			$unit = 'GB';
		}
		else if ($val >= $li * 1000)
		{
			$val = round($val / (1048576 / $sep)) / $sep;
			$unit = 'MB';
		}
		else if ($val >= $li)
		{
			$val = round($val / (1024 / $sep)) / $sep;
			$unit = 'KB';
		}
		if ($unit != 'Bytes')
		{
			$retval = number_format($val, $sepa, '.', ',');
		}
		else
		{
			$retval = number_format($val, 0, '.', ',');
		}
		return $retval . ' ' . $unit;
	}

	function gzip_four_chars($val)
	{
		for ($i = 0; $i < 4; $i ++)
		{
			$return .= chr($val % 256);
			$val = floor($val / 256);
		}
		return $return;
	}

	function export_header($dumptype = 'Standard Backup', $vol = 'NULL', $step = 1, $finish = 1, $dbstrlen = 0)
	{
		global $forums, $bboptions, $_INPUT;
		return '# key: ' . base64_encode(date('Y-m-d H:i:s') . ",$dumptype,$vol,$step,$finish, $dbstrlen, " . $this->md5_check . '_') . "\n\n" .
			"# MolyX SQL DUMP\n" .
			"# version 1.0.0\n" .
			"# DUMP Siteurl: {$bboptions['bburl']}\n" .
			"# DUMP Type: $dumptype\n" .
			"# Current Volume: $vol\n" .
			'# ALL DONE: ' . ($finish ? 'TRUE' : 'FALSE') . "\n" .
			'# DUMP TIME: ' . $forums->func->get_time(TIMENOW) . "\n\n" .
			"# THIS FILE BASED ON MOLYX\n" .
			"# If You Any Questions, Please Visit: www.molyx.com\n" .
			"# --------------------------------------------------------\n" .
			"# start export\n\n";
	}
}

$output = new mysql();
$output->show();
?>