<?php
/**
 * Documentor for XOOPS
 * Documentation system for XOOPS based on Common Utilities
 *
 * Copyright © 2014 Eduardo Cortés
 * -----------------------------------------------------------------
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * -----------------------------------------------------------------
 * @package      Documentor
 * @author       Eduardo Cortés <yo@eduardocortes.mx>
 * @copyright    2009 - 2014 Eduardo Cortés
 * @license      GPL v2
 * @link         http://eduardocortes.mx
 * @link         http://xoopsmexico.net
 */

require __DIR__ . '/header.php';

if (!isset($id) || (isset($id) && '' == $id)) {
    RDFunctions::error_404();
}

$browser = $_SERVER['HTTP_USER_AGENT'];
$pos = mb_stripos($browser, 'Mozilla/5');
define('RD_LOCATION', 'content');

/**
 * @desc Muestra el contenido completo de una sección
 * @param \RDResource $res
 * @param \RDSection  $section
 */
function showSection(RDResource $res, RDSection $section)
{
    global $xoopsUser, $xoopsModuleConfig, $xoopsOption, $xoopsTpl, $xoopsConfig, $standalone;

    require __DIR__ . '/header.php';

    $xoopsTpl->assign('xoops_pagetitle', $section->getVar('title'));

    // Resource data
    $resource = [
        'id' => $res->id(),
        'title' => $res->getVar('title'),
        'link' => $res->permalink(),
        'reads' => $res->getVar('reads'),
    ];

    $res->add_read($res);

    // Section tree
    $db = XoopsDatabaseFactory::getDatabaseConnection();

    $index = [];
    RDFunctions::sections_tree_index(0, 0, $res, '', '', false, $index);

    $standalone = $xoopsModuleConfig['standalone'];

    RMTemplate::getInstance()->add_jquery();
    RMTemplate::getInstance()->add_script('jquery.dotdotdot.min.js', 'docs', [ 'footer' => 1 ]);
    RMTemplate::getInstance()->add_script('docs.min.js', 'docs', [ 'footer' => 1 ]);

    if ($xoopsModuleConfig['standalone']) {
        include RMEvents::get()->run_event('docs.section.template', RMTemplate::getInstance()->get_template('docs-display-section.php', 'module', 'docs'));
        RDFunctions::standalone();
    } else {
        RMTemplate::getInstance()->add_style('docs.min.css', 'docs');
    }

    $sql = 'SELECT * FROM ' . $db->prefix('mod_docs_sections') . " WHERE id_res='" . $res->id() . "' AND parent = '0' ORDER BY `order`";
    $result = $db->query($sql);
    $i = 1;
    $first_section = 0;
    $number = 1;
    $located = false; // Check if current position has been located
    while (false !== ($row = $db->fetchArray($result))) {
        $sec = new RDSection();
        $sec->assignVars($row);

        if (0 === $i) {
            $first_section = $row['id_sec'];
        }

        if ($sec->id() == $section->id()) {
            $number = $i;
            $located = true;
        }

        if (isset($sprev) && $sec->id() == $section->id()) {
            $prev_section = [
                'id' => $sprev->id(),
                'title' => $sprev->getVar('title'),
                'link' => $sprev->permalink(),
            ];
        }

        if ($number == $i - 1 && $located) {
            $next_section = [
                'id' => $sec->id(),
                'title' => $sec->getVar('title'),
                'link' => $sec->permalink(),
            ];
            break;
        }
        $i++;

        $sprev = $sec;
    }

    $GLOBALS['rd_section_number'] = $number;

    $sections = RDFunctions::get_section_tree($section->id(), $res, $number, true);
    array_walk($sections, 'rd_insert_edit');
    // Check last modification date
    $last_modification = 0;

    foreach ($sections as $sec) {
        if ($sec['modified'] > $last_modification) {
            $last_modification = $sec['modified'];
            $last_author = [
                'id' => $sec['author'],
                'name' => $sec['author_name'],
            ];
        }
    }

    // Event
    $sections = RMEvents::get()->run_event('docs.show.section', $sections, $res, $section);

    // URLs
    if ($xoopsModuleConfig['permalinks']) {
        /**
         * @todo Generate friendly links
         */
        if (RMFunctions::plugin_installed('topdf')) {
            $pdf_book_url = RDFunctions::url() . '/pdfbook/' . $section->id() . '/';
            $pdf_section_url = RDFunctions::url() . '/pdfsection/' . $section->id() . '/';
        }
        $print_book_url = RDFunctions::url() . '/printbook/' . $section->id() . '/';
        $print_section_url = RDFunctions::url() . '/printsection/' . $section->id() . '/';
        if (RDFunctions::new_resource_allowed($xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS)) {
            $publish_url = RDFunctions::url() . '/publish/';
        }
    } else {
        if (RMFunctions::plugin_installed('topdf')) {
            $pdf_book_url = XOOPS_URL . '/modules/docs/index.php?page=content&amp;id=' . $section->id() . '&amp;action=pdfbook';
            $pdf_section_url = XOOPS_URL . '/modules/docs/index.php?page=content&amp;id=' . $section->id() . '&amp;action=pdfsection';
        }
        $print_book_url = XOOPS_URL . '/modules/docs/index.php?page=content&amp;id=' . $section->id() . '&amp;action=printbook';
        $print_section_url = XOOPS_URL . '/modules/docs/index.php?page=content&amp;id=' . $section->id() . '&amp;action=printsection';
        if (RDFunctions::new_resource_allowed($xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS)) {
            $publish_url = RDFunctions::url() . '/?action=publish';
        }
    }

    RDFunctions::breadcrumb();
    RMBreadCrumb::get()->add_crumb($res->getVar('title'), $res->permalink());
    RMBreadCrumb::get()->add_crumb($section->getVar('title'), $section->permalink());

    include RMEvents::get()->run_event('docs.section.template', RMTemplate::getInstance()->path('docs-display-section.php', 'module', 'docs'));

    require __DIR__ . '/footer.php';
}

/**
 * This function create a page to print or pdf.
 * @param mixed $all
 */
function rd_section_forprint($all = 0)
{
    global $section, $res, $xoopsConfig;

    require __DIR__ . '/header.php';

    $xoops_langcode = $xoopsTpl->get_template_vars('xoops_langcode');
    $xoops_charset = $xoopsTpl->get_template_vars('xoops_charset');
    $xoops_pagetitle = $xoopsTpl->get_template_vars('xoops_pagetitle');
    $xoops_sitename = $xoopsTpl->get_template_vars('xoops_sitename');
    $xoops_css = $xoopsTpl->get_template_vars('xoops_themecss');

    if ($all) {
        $toc = [];
        RDFunctions::sections_tree_index(0, 0, $res, '', '', false, $toc, true);

        include RMEvents::get()->run_event('docs.print.template', RMTemplate::getInstance()->get_template('docs-print-section.php', 'module', 'docs'));
    } else {
        $toc = RDFunctions::get_section_tree($section->id(), $res, '1', true);

        include RMEvents::get()->run_event('docs.print.template', RMTemplate::getInstance()->get_template('docs-print-section.php', 'module', 'docs'));
    }
}

function rd_section_forpdf($all = 0)
{
    global $section, $res, $xoopsConfig, $xoopsModuleConfig;

    $plugin = RMFunctions::load_plugin('topdf');

    if ($xoopsModuleConfig['permalinks']) {
        $print_book_url = RDFunctions::url() . '/printbook/' . $section->id() . '/';
        $print_section_url = RDFunctions::url() . '/printsection/' . $section->id() . '/';
    } else {
        $print_book_url = XOOPS_URL . '/modules/docs/index.php?page=content&amp;id=' . $section->id() . '&amp;action=printbook';
        $print_section_url = XOOPS_URL . '/modules/docs/index.php?page=content&amp;id=' . $section->id() . '&amp;action=printsection';
    }

    // This options only works when pdfmyurl is enabled on topdf plugin
    $options = [
        '--filename' => $res->getVar('title') . '.pdf',
        '--header-left' => $res->getVar('title'),
        '--header-right' => $xoopsConfig['sitename'],
        '--header-line' => '1',
    ];

    header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');

    $plugin->create_pdf_url($all ? $print_book_url : $print_section_url, $res->getVar('title') . '.pdf', $options);
}

// Sección
$section = new RDSection($id, isset($res) ? $res : null);

if ($section->isNew()) {
    RDFunctions::error_404();
}

$res = new RDResource($section->getVar('id_res'));

//Verificamos si es una publicación aprobada
if ($res->isNew()) {
    RDFunctions::error_404();
}

// Check if section is a top parent
/*if ($section->getVar('parent')>0){
    $top = RDFunctions::super_parent($section->getVar('parent'));
    header('location: '.html_entity_decode($top->permalink()).'#'.$section->getVar('nameid'));
    die();
}*/

if (!$res->getVar('approved')) {
    redirect_header(RDURL, 0, __('This content is not available!', 'docs'));
    die();
}
// Comprobamos permisos
if (!$res->isAllowed($xoopsUser ? $xoopsUser->groups() : XOOPS_GROUP_ANONYMOUS)) {
    redirect_header(RDURL, 0, __('You are not allowed to read this content!', 'docs'));
    die();
}

// Select correct operation
$action = rmc_server_var($_GET, 'action', '');

switch ($action) {
    case 'printbook':
        rd_section_forprint(1);
        die();
    case 'printsection':
        rd_section_forprint(0);
        die();
    case 'pdfbook':
        rd_section_forpdf(1);
        break;
    case 'pdfsection':
        rd_section_forpdf(0);
        break;
    default:
        showSection($res, $section);
        break;
}
