<?php


# 中国行政区数据采集器
# 数据来源： 国家统计局 http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2015/index.html
# 统计用区划代码和城乡划分代码编制规则 http://www.stats.gov.cn/tjsj/tjbz/200911/t20091125_8667.html


$province = [11, 12, 13, 14, 15, 21, 22, 23, 31, 32, 33, 34, 35, 36, 37, 41, 42, 43, 44, 45, 46, 50, 51, 52, 53, 54, 61, 62, 63, 64, 65, 71, 81, 82];
$level_list = [ 1 => [2, '省级'], 
				2 => [4, '城级'], 
				3 => [6, '县级'], 
				4 => [9, '乡级'], 
				5 => [12, '村级'],
			  ];

// 联动级别 默认4级地区联动
$level = 4;
$year = 2015;
$filename = 'district.txt';
$base_url = "http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/{$year}/";


$file = fopen($filename, 'w+');


$len_id = $level_list[$level][0];

// 生成省份
$content = file_get_contents($base_url. 'index.html');
$province_list = [];
preg_match_all('/class=\'provincetr\'>([\s\S]*?)<\/tr>/', $content, $matches);

foreach ($matches[1] as $tr_strings)
{
	preg_match_all('/<td><a\shref=\'(\d*?)\.html\'>(.*?)<br\/><\/a><\/td>/', $tr_strings, $a_matches);
	foreach ($a_matches[1] as $key => $province_id) {
		$province_item = [];
		$province_item['id'] = $province_id . str_repeat('0', $len_id-2);
		$province_item['name'] = $a_matches[2][$key];

		fwrite($file, $province_item['id'] . ' ' . $province_item['name'] . ' ' . '0' . PHP_EOL);
		if ($level == 1) continue;

		// 生成城市
		$province_id = substr($province_item['id'], 0, 2);
		$city_uri = $base_url . $province_id . '.html';
		$city_content = file_get_contents($city_uri);

		$city_list = [];
		preg_match_all('/class=\'citytr\'><td><a\shref=\'.*?\.html\'>(\d*?)<\/a><\/td><td><a\shref=\'.*?\'>(.*?)<\/a><\/td>/', $city_content, $c_matches);

		foreach ($c_matches[1] as $c_key => $city_id) {
			$city_item = [];
			$city_item['id'] = substr($city_id, 0, $len_id);
			$city_item['name'] = $c_matches[2][$c_key];

			fwrite($file, $city_item['id'] . ' ' . $city_item['name'] . ' ' . $province_item['id'] . PHP_EOL);
			if ($level == 2) continue;

			// 生成区县
			$city_id = substr($city_item['id'], 0, 4);
			$county_uri = $base_url . $province_id . '/'. $city_id .'.html';
			$county_content = file_get_contents($county_uri);

			$county_list = [];
			preg_match_all('/class=\'countytr\'><td><a\shref=\'.*?\'>(\d*?)<\/a><\/td><td><a\shref=\'.*?\'>(.*?)<\/a><\/td><\/tr>/', $county_content, $ct_matches);

			foreach ($ct_matches[1] as $a_key => $county_id) {
				$county_item = [];
				$county_item['id'] = substr($county_id, 0, $len_id);
				$county_item['name'] = $ct_matches[2][$a_key];

				fwrite($file, $county_item['id'] . ' ' . $county_item['name'] . ' ' . $city_item['id'] . PHP_EOL);
				if ($level == 3) continue;

				// 生成乡镇
				$county_id = substr($county_item['id'], 0, 6);
				$town_uri = $base_url . $province_id . '/' . substr($city_id, 2, 2) . '/' . $county_id . '.html';
				$town_content = file_get_contents($town_uri);

				$town_list = [];
				preg_match_all('/class=\'towntr\'><td><a\shref=\'.*?\'>(\d*?)<\/a><\/td><td><a\shref=\'.*?\'>(.*?)<\/a><\/td>/', $town_content, $t_matches);

				foreach ($t_matches[1] as $t_key => $town_id) {
					$town_item = [];
					$town_item['id'] = substr($town_id, 0, $len_id);
					$town_item['name'] = $t_matches[2][$t_key];

					fwrite($file, $town_item['id'] . ' ' . $town_item['name'] . ' ' . $county_item['id'] . PHP_EOL);
					if ($level == 4) continue;

					$town_list[] = $town_item;
				}

				$county_list[] = $county_item;
			}

			$city_list[] = $city_item;
		}
		$province_list[] = $province_item;
	}
}

fclose($file);
