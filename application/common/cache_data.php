<?php

function getCountryList()
{
    $key = 'country_list';

    $cCountryListData = cache($key);
    if ($cCountryListData) {
        if (!is_array($cCountryListData)) {
            $cCountryListData = json_decode($cCountryListData, true);
        }
        return $cCountryListData;
    } else {
        $cCountryListData = db('country')->where('status', '=', 1)->order('sort desc')->select();
        cache($key, $cCountryListData);
        return $cCountryListData;
    }
}


function getLevelTypeList()
{
    $key = 'level_type:list';

    $levelTypeListData = cache($key);
    if ($levelTypeListData) {
        return $levelTypeListData;
    } else {
        cache($key, db('level_type')->order('sort desc')->select());
        return cache($key);
    }
}