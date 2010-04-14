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
require ('./global.php');

class importers
{
	function show()
	{
		global $forums, $_INPUT, $bbuserinfo;
		$admin = explode(',', SUPERADMIN);
		if (!in_array($bbuserinfo['id'], $admin) && !$forums->adminperms['caneditothers'])
		{
			$forums->admin->print_cp_error($forums->lang['nopermissions']);
		}
		$forums->admin->nav[] = array('importers.php', $forums->lang['importers']);
		switch ($_INPUT['do'])
		{
			case 'doinit':
				$this->doinit();
				break;
			default:
				$this->start();
				break;
		}
	}

	function start()
	{
		global $forums, $_INPUT;
		$pagetitle = $forums->lang['importers'];
		$detail = $forums->lang['importersdesc'];
		$forums->admin->print_cp_header($pagetitle, $detail);
		$imlist = array();
		$handle = @opendir(ROOT_PATH . "importers");
		while ($file = @readdir($handle))
		{
			if (preg_match("/^import_(.*)\./", $file, $regs))
			{
				$importfile = fopen(ROOT_PATH . "importers/$file", "r");
				fseek($importfile, 9);
				$imlist[] = array($regs[1], utf8_htmlspecialchars(str_replace("// ", "", trim(fgets($importfile, 255)))));
				flush();
			}
		}
		@closedir($handle);
		if (count($imlist) < 1)
		{
			$imlist[] = array('membersarea', $forums->lang['noimporterscript1']);
			$imlist[] = array('membersarea', $forums->lang['noimporterscript2']);
			$imlist[] = array('membersarea', $forums->lang['noimporterscript3']);
		}
		$forums->admin->print_form_header(array(1 => array('do' , 'doinit')));
		$forums->admin->columns[] = array("&nbsp;" , "40%");
		$forums->admin->columns[] = array("&nbsp;" , "60%");
		$forums->admin->print_table_start($forums->lang['importers']);
		$forums->admin->print_cells_row(array("<strong>" . $forums->lang['selectimporterscript'] . "</strong>" ,
				$forums->admin->print_input_select_row('importscript', $imlist, '', "size='5'")
				));
		$forums->admin->print_form_submit($forums->lang['startimport']);
		$forums->admin->print_table_footer();
		$forums->admin->print_form_end();
		$forums->admin->print_cp_footer();
	}

	function doinit()
	{
		global $forums, $_INPUT;
		if (!$_INPUT['importscript'])
		{
			$forums->main_msg = $forums->lang['plzselectimporter'];
			$this->start();
		}
		require_once(ROOT_PATH . 'includes/adminfunctions_importers.php');
		$forums->cache['cron'] = TIMENOW + 86400;
		$forums->cache['settings']['bbactive'] = '0';
		$newinit = new adminfunctions_importers();
		$forums->func->update_cache(array('name' => 'cron', 'array' => 0));
		$forums->func->update_cache(array('name' => 'settings', 'array' => 1));
		if ($_INPUT['importscript'] == "membersarea")
		{
			header("Location: http://www.molyx.com/");
		}
		else
		{
			$script = $_INPUT['importscript'];
			$sess = $forums->sessionurl;
			header("Location: ../importers/import_$script.php?" . $sess);
		}
	}
}

$output = new importers();
$output->show();

?>