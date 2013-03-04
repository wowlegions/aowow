﻿<?php

if (!defined('AOWOW_REVISION'))
    die('invalid access');

$pageData = array(
    'summary' => '[]',
    'items'   => []
);
$compareString  = '';

// prefer $_GET over $_COOKIE
if (!empty($_GET['compare']))
    $compareString = $_GET['compare'];
else if (!empty($_COOKIE['compare_groups']))
    $compareString = urldecode($_COOKIE['compare_groups']);

if ($compareString)
{
    $sets  = explode(";", $compareString);
    $items = [];
    foreach ($sets as $set)
    {
        $itemsting = explode(":", $set);
        $outString = [];
        foreach ($itemsting as $substring)
        {
            $params  = explode(".", $substring);
            $items[] = (int)$params[0];
            while (sizeof($params) < 7)
                $params[] = 0;

            $outString[] = "[".implode(',', $params)."]";

            // MATCH() AGAINST() for integers would be nice...
            $res = DB::Aowow()->SelectRow(
                "SELECT Id FROM ?_itemset WHERE
                item1 = ? OR item2 = ? OR item3 = ? OR item4 = ? OR item5 = ? OR
                item6 = ? OR item7 = ? OR item8 = ? OR item9 = ? OR item10 = ?",
                (int)$params[0], (int)$params[0], (int)$params[0], (int)$params[0], (int)$params[0], (int)$params[0], (int)$params[0], (int)$params[0], (int)$params[0], (int)$params[0]
            );

            if ($res)
                $piecesAssoc[(int)$params[0]] = $res['Id'];
        }
        $outSet[] = "[".implode(',', $outString)."]";
    }
    $pageData['summary'] = "[".implode(',', $outSet)."]";

    $iList = new ItemList(array(['i.entry', $items]));
    foreach ($iList->container as $item)
    {
        $item->getJsonStats();
        $stats = [];
        foreach ($item->json as $k => $v)
            $stats[] = is_numeric($v) || $v[0] == "{" ? '"'.$k.'":'.$v.'' : '"'.$k.'":"'.$v.'"';

        foreach ($item->itemMods as $k => $v)
            if ($v)
                $stats[] = '"'.Util::$itemMods[$k].'":'.$v;

        $pageData['items'][] = [
            $item->Id,
            Util::jsEscape(Util::localizedString($item->template, 'name')),
            $item->template['Quality'],
            $item->template['icon'],
            "{".implode(",", $stats)."}"
        ];
    }
}

// Announcements
$announcements = DB::Aowow()->Select('SELECT * FROM ?_announcements WHERE flags & 0x10 AND (page = "compare" OR page = "*")');
foreach ($announcements as $k => $v)
    $announcements[$k]['text'] = Util::localizedString($v, 'text');

$page = array(
    'title'  => Lang::$compare['compare'],
    'tab'    => 1,
    'reqCSS' => array(
        array('path' => 'template/css/Summary.css', 'condition' => false),
        array('path' => 'template/css/Summary_ie6.css', 'condition' => 'lte IE 6'),
    ),
    'reqJS'  => array(
        array('path' => 'template/js/Draggable.js'),
        array('path' => 'template/js/filters.js'),
        array('path' => 'template/js/Summary.js'),
        array('path' => 'template/js/swfobject.js'),
        array('path' => '?data=weight-presets.gems.enchants.itemsets'),
    ),
);


$smarty->updatePageVars($page);
$smarty->assign('data', $pageData);
$smarty->assign('lang', array_merge(Lang::$main, Lang::$compare));
$smarty->assign('announcements', $announcements);
$smarty->assign('mysql', DB::Aowow()->getStatistics());
$smarty->display('compare.tpl');

?>
