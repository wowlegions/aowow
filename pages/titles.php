<?php

if (!defined('AOWOW_REVISION'))
    die('illegal access');


$cat      = Util::extractURLParams($pageParam)[0];
$path     = [0, 10];
$cacheKey = implode('_', [CACHETYPE_PAGE, TYPE_TITLE, -1, isset($cat) ? $cat : -1, User::$localeId]);
$title    = [ucFirst(Lang::$game['titles'])];

$path[] = $cat;                                             // should be only one parameter anyway

if (isset($cat))
    array_unshift($title, Lang::$title['cat'][$cat]);

if (!$smarty->loadCache($cacheKey, $pageData))
{
    $titles = new TitleList(isset($cat) ? array(['category', (int)$cat]) : []);
    $listview = $titles->getListviewData();

    $sources = array(
        4  => [],                                           // Quest
        12 => [],                                           // Achievement
        13 => []                                            // DB-Text
    );

    // parse sources
    foreach ($listview as $lvTitle)
    {
        if(!isset($lvTitle['source']))
            continue;

        foreach (array_keys($sources) as $srcKey)
            if (isset($lvTitle['source'][$srcKey]))
                $sources[$srcKey] = array_merge($sources[$srcKey], $lvTitle['source'][$srcKey]);
    }

    // replace with suitable objects
    if (!empty($sources[4]))
        $sources[4] = new QuestList(array(['Id', $sources[4]]));

    if (!empty($sources[12]))
        $sources[12] = new AchievementList(array(['Id', $sources[12]]));

    if (!empty($sources[13]))
        $sources[13] = DB::Aowow()->SELECT('SELECT *, Id AS ARRAY_KEY FROM ?_sourceStrings WHERE Id IN (?a)', $sources[13]);

    foreach ($listview as $k => $lvTitle)
    {
        if(!isset($lvTitle['source']))
            continue;

        // Quest-source
        if (isset($lvTitle['source'][4]))
        {
            $ids = $lvTitle['source'][4];
            $listview[$k]['source'][4] = [];
            foreach ($ids as $id)
                $listview[$k]['source'][4][] = $sources[4]->container[$id]->getSourceData();
        }

        // Achievement-source
        if (isset($lvTitle['source'][12]))
        {
            $ids = $lvTitle['source'][12];
            $listview[$k]['source'][12] = [];
            foreach ($ids as $id)
                $listview[$k]['source'][12][] = $sources[12]->container[$id]->getSourceData();
        }

        // other source (only one item possible, so no iteration needed)
        if (isset($lvTitle['source'][13]))
            $listview[$k]['source'][13] = [$sources[13][$lvTitle['source'][13][0]]];

        $listview[$k]['source'] = json_encode($listview[$k]['source']);
    }

    $pageData['page'] = $listview;

    $smarty->saveCache($cacheKey, $pageData);
}

// Announcements
$announcements = DB::Aowow()->Select('SELECT * FROM ?_announcements WHERE flags & 0x10 AND (page = "titles" OR page = "*")');
foreach ($announcements as $k => $v)
    $announcements[$k]['text'] = Util::localizedString($v, 'text');

$page = array(
    'tab'   => 0,                                           // for g_initHeader($tab)
    'title' => implode(" - ", $title),
    'path'  => "[".implode(", ", $path)."]",
);

$smarty->updatePageVars($page);
$smarty->assign('lang', Lang::$main);
$smarty->assign('data', $pageData);
$smarty->assign('mysql', DB::Aowow()->getStatistics());
$smarty->assign('announcements', $announcements);
$smarty->display('titles.tpl');

?>
