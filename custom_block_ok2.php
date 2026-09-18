<?php
if( !javad() ) die("درحال آپدیت ....");

define("Root_Tem", Root . "/counters/tem/$blogid/");

$pattern_contetnt = "/<RB:CBC((=)?([A-Za-z0-9\ا\ب\پ\ت\ث\ج\چ\ح\خ\د\ذ\ر\ز\ژ\س\ش\ص\ض\ط\ظ\ع\غ\ف\ق\ک\گ\ل\م\ن\و\ه\ی\ك\آ\ي\ئ\-\_\.\ \?]+)?(=)?(file?|text?|editor?|textarea?|code_editor?|selectbox?\(.*\)?|note?))?>(.*)<\/RB:CBC>/Uis";

$pattern_contetnt2 = "/<RB:CBC((=)?([A-Za-z0-9\ا\ب\پ\ت\ث\ج\چ\ح\خ\د\ذ\ر\ز\ژ\س\ش\ص\ض\ط\ظ\ع\غ\ف\ق\ک\گ\ل\م\ن\و\ه\ی\ك\آ\ي\ئ\-\_\.\ \?]+)?(=)?(file?|text?|editor?|textarea?|code_editor?|selectbox?\(.*\)?|note?))?>/Uis";

$pattern_contetnt3 = "/(<RB:CBC((=)?([A-Za-z0-9\ا\ب\پ\ت\ث\ج\چ\ح\خ\د\ذ\ر\ز\ژ\س\ش\ص\ض\ط\ظ\ع\غ\ف\ق\ک\گ\ل\م\ن\و\ه\ی\ك\آ\ي\ئ\-\_\.\ \?]+)?(=)?(file?|text?|editor?|textarea?|code_editor?|selectbox?\(.*\)?|note?))?>)(.*)(<\/RB:CBC>)/Uis";

$pattern_contetnt4 = "/((<RB:CBC((=)?([A-Za-z0-9\ا\ب\پ\ت\ث\ج\چ\ح\خ\د\ذ\ر\ز\ژ\س\ش\ص\ض\ط\ظ\ع\غ\ف\ق\ک\گ\ل\م\ن\و\ه\ی\ك\آ\ي\ئ\-\_\.\ \?]+)?(=)?(file?|text?|editor?|textarea?|code_editor?|selectbox?\(.*\)?|note?))?>)(.*))(<\/RB:CBC>)/Uis";


function preg_replace_nth($pattern, $replacement, $subject, $nth = 1) {
    return preg_replace_callback($pattern,
        function ($found) use (&$pattern, &$replacement, &$nth) {
            $nth--;
            if ($nth == 0) return preg_replace($pattern, $replacement, reset($found));
            return reset($found);
        }, $subject, $nth);
}

function str_replace_n($search, $replace, $subject, $occurrence) {
    $search = preg_quote($search, '/');
    return preg_replace("/((?:(?:.*?$search){" . --$occurrence . "}.*?))$search/s", "$1$replace", $subject);
}

function nthReplace($search, $replace, $subject, $n) {
    if ($subject === '' || $search === '' || !is_string($search) || !is_string($subject)) {
        return $subject;
    }
    if (!is_int($n) || $n < 0) {
        return $subject;
    }
    $count = 0;
    $pos = 0;
    while (true) {
        $foundPos = strpos($subject, $search, $pos);
        if ($foundPos === false) break;
        if ($count === $n) {
            return substr_replace($subject, $replace, $foundPos, strlen($search));
        }
        $count++;
        $pos = $foundPos + strlen($search);
    }
    return $subject;
}

function str_replace_nth($search, $replace, $subject, $nth)
{
    global $pattern_contetnt, $pattern_contetnt4;

    if ($search === '' || $subject === '') return $subject;

    $count = 0;
    $pos = 0;
    $positions = [];

    while (($pos = strpos($subject, $search, $pos)) !== false) {
        $positions[] = $pos;
        $pos += strlen($search);
    }

    if ($nth < 1 || $nth > count($positions)) {
        return $subject;
    }

    $target_pos = $positions[$nth - 1];

    return substr_replace($subject, $replace, $target_pos, strlen($search));
}


function update_cbc($insert0)
{
    global $pattern_contetnt4, $val;

    if (empty($val)) return $insert0;

    if (!preg_match_all($pattern_contetnt4, $insert0, $content2, PREG_OFFSET_CAPTURE)) {
        return $insert0;
    }

    $sorted_val = $val;
    uksort($sorted_val, function($a, $b) {
        return (int)$a - (int)$b;
    });
    $values = array_values($sorted_val);

    $replacements = [];

    foreach ($content2[0] as $key3 => $matched) {
        $full_tag = $matched[0];
        $offset   = $matched[1];

        if (!isset($values[$key3])) continue;
        $new_value = $values[$key3];

        $open_tag = $content2[2][$key3][0];
        $new_tag = $open_tag . $new_value . '</RB:CBC>';

        if ($full_tag !== $new_tag) {
            $replacements[] = [
                'offset' => $offset,
                'length' => strlen($full_tag),
                'new'    => $new_tag
            ];
        }
    }

    usort($replacements, function($a, $b) {
        return $b['offset'] - $a['offset'];
    });

    foreach ($replacements as $r) {
        $insert0 = substr_replace($insert0, $r['new'], $r['offset'], $r['length']);
    }

    return $insert0;
}

function replaceCBC($html, $texts) {
    $index = 0;
    return preg_replace_callback('/(<RB:CBC=.*?>)(.*?)(<\/RB:CBC>)/', function ($matches) use (&$index, $texts) {
        return $matches[1] . htmlspecialchars($texts[$index++]) . $matches[3];
    }, $html);
}


// ============================================================
// توابع کمکی برای CBI
// ============================================================
if (!function_exists('rb_get_cbi_items')) {
    function rb_get_cbi_items($block_content) {
        $parts = preg_split('/(?=<RB:CBI>)/i', $block_content);
        $items = [];
        foreach ($parts as $part) {
            if (preg_match('/<RB:CBI>.*?<\/RB:CBI>/is', $part, $m)) {
                $items[] = $m[0];
            }
        }
        return $items;
    }
}

if (!function_exists('rb_insert_after_nth')) {
    function rb_insert_after_nth($search, $insert, $subject, $nth) {
        if ($search === '' || $subject === '') return $subject;
        $count = 0;
        $pos = 0;
        while (true) {
            $foundPos = strpos($subject, $search, $pos);
            if ($foundPos === false) break;
            if ($count === $nth) {
                $insertPos = $foundPos + strlen($search);
                return substr_replace($subject, $insert, $insertPos, 0);
            }
            $count++;
            $pos = $foundPos + strlen($search);
        }
        return $subject;
    }
}

if (!function_exists('rb_remove_nth_occurrence')) {
    function rb_remove_nth_occurrence($search, $subject, $nth) {
        if ($search === '' || $subject === '') return $subject;
        $count = 0;
        $pos = 0;
        while (true) {
            $foundPos = strpos($subject, $search, $pos);
            if ($foundPos === false) break;
            if ($count === $nth) {
                return substr_replace($subject, '', $foundPos, strlen($search));
            }
            $count++;
            $pos = $foundPos + strlen($search);
        }
        return $subject;
    }
}

if (!function_exists('rb_render_cbi_field')) {
    function rb_render_cbi_field($key, $field_idx, $key_type, $key_title, $value, $title_btn_upload) {
        $out = '';
        if ($key_type == 'file') {
            $out .= '<div class="row_form cbi_field_file"><div class="col_25">' . $key_title . ' : </div>'
                 .  '<div class="col_75">'
                 .  '<div class="form-upload col_30 fxc">'
                 .  '<label for="file-upload-' . $key . '_' . $field_idx . '">' . $title_btn_upload
                 .  '<input type="file" id="file-upload-' . $key . '_' . $field_idx . '" class="file-upload" name="custom_file[' . $key . '][' . $field_idx . ']">'
                 .  '</label>'
                 .  '<p id="filename-' . $key . '_' . $field_idx . '" class="filename"></p>'
                 .  '</div>'
                 .  '<div class="col_70 fxc">'
                 .  '<a id="a_' . $key . '_' . $field_idx . '" href="' . $value . '" target="_blank"><img id="img_' . $key . '_' . $field_idx . '" src="' . $value . '" width="30" height="35"/></a>'
                 .  '<input class="i_' . $key . '_' . $field_idx . '" name="custom_block[' . $key . '][' . $field_idx . ']" dir="auto" value="' . h_encode($value) . '" type="text">'
                 .  '</div></div></div>'
                 .  '<script>'
                 .  '$("#file-upload-' . $key . '_' . $field_idx . '").change(function(){'
                 .  'var filepath=this.value;var m=filepath.match(/([^\\\/]+)$/);var filename=m[1];'
                 .  '$("#filename-' . $key . '_' . $field_idx . '").html(filename);});'
                 .  '</script>';
        } elseif ($key_type == 'text') {
            $out .= '<div class="row_form"><div class="col_25">' . $key_title . ' : </div><div class="col_75"><input name="custom_block[' . $key . '][' . $field_idx . ']" dir="auto" value="' . h_encode($value) . '" type="text"></div></div>';
        } elseif ($key_type == 'textarea') {
            $out .= '<div class="row_form"><div class="col_25">' . $key_title . ' : </div><div class="col_75"><textarea name="custom_block[' . $key . '][' . $field_idx . ']" dir="auto">' . h_encode($value) . '</textarea></div></div>';
        } elseif ($key_type == 'code_editor') {
            $out .= '<div class="row_form"><div class="col_25">' . $key_title . ' : </div><div class="col_75"><textarea class="themecode" name="custom_block[' . $key . '][' . $field_idx . ']" dir="auto">' . h_encode($value) . '</textarea></div></div>';
        } elseif ($key_type == 'editor') {
            $out .= '<div class="row_form"><div class="col_100">' . $key_title . ' : </div></div><div class="row_form"><div class="col_100"><textarea class="editor_field" id="editor_' . $field_idx . '" name="custom_block[' . $key . '][' . $field_idx . ']" dir="auto">' . $value . '</textarea></div></div>';
        } elseif (strstr($key_type, 'selectbox')) {
            preg_match("/selectbox\((.*)\)/Uis", $key_type, $match);
            $option2 = explode("|", $match[1]);
            $out .= '<div class="row_form"><div class="col_25">' . $key_title . ' : </div><div class="col_75"><select name="custom_block[' . $key . '][' . $field_idx . ']" dir="auto">';
            foreach ($option2 as $option3) {
                $option4 = explode("=", $option3);
                $selectedok = ($value == $option4[1]) ? 'selected' : '';
                $out .= '<option ' . $selectedok . ' value="' . $option4[1] . '">' . $option4[0] . '</option>';
            }
            $out .= '</select></div></div>';
        } elseif ($key_type == 'note') {
            $out .= '<textarea name="custom_block[' . $key . '][' . $field_idx . ']" style="display:none">' . $value . '</textarea>';
        }
        return $out;
    }
}

if (!function_exists('rb_render_cbi_item')) {
    function rb_render_cbi_item($key, $idx, $total_items, $one_item, $title_btn_upload, $pattern_contetnt, $field_offset = null) {
        preg_match_all($pattern_contetnt, $one_item, $one_content);

        if ($field_offset === null) {
            $field_offset = ($idx + 1) * 100;
        }

        $html  = '<div class="cbi_item_wrapper" data-block="' . $key . '" data-index="' . $idx . '" style="border:1px dashed #ccc;padding:10px;margin:10px 0;border-radius:5px;background:#fafafa;">';
		$html .= '<div class="cbi_item_header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">';
		$html .= '<span style="font-weight:bold;color:#2196F3;">آیتم ' . ($idx + 1) . '</span>';

		$html .= '<div class="cbi_item_header_actions" style="display:flex;gap:6px;">';

		// دکمه افزودن با SVG
		$html .= '<button type="button" class="cbi_add" data-block="' . $key . '" data-index="' . $idx . '" title="افزودن آیتم جدید">'
			   . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>'
			   . '</button>';

		// دکمه حذف با SVG (فقط اگر بیش از یک آیتم باشه)
		if ($total_items > 1) {
			$html .= '<button type="button" class="cbi_delete" data-block="' . $key . '" data-index="' . $idx . '" title="حذف این آیتم">'
				   . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>'
				   . '</button>';
		}

		$html .= '</div>'; // پایان header_actions
		$html .= '</div>'; // پایان header
        $html .= '<div class="cbi_item_fields">';

        foreach ($one_content[6] as $k => $value) {
            $field_idx = $field_offset + $k;
            $key_type  = $one_content[5][$k];
            $key_title = $one_content[3][$k];
            $html .= rb_render_cbi_field($key, $field_idx, $key_type, $key_title, $value, $title_btn_upload);
        }

        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}


if (!$ajax) {
    echo '<a class="btn success" href="https://rozblog.com/theme_help.html#custom_block2" target="_blank">کدهای بلاک های دلخواه</a>';

    $update_staic = "v22.1";

    echo '<link rel="stylesheet" href="/js/code_mirorW3/codemirror.css?' . $update_staic . '">' . "\n";
    echo '<script src="/js/codemirror-5.59.2/lib/codemirror.js"></script>' . "\n";
    echo '<script src="/js/codemirror-5.59.2/addon/edit/matchtags.js?' . $update_staic . '"></script>' . "\n";
    echo '<link rel="stylesheet" href="/js/codemirror-5.59.2/addon/fold/foldgutter.css?' . $update_staic . '">' . "\n";
    echo '<script src="/js/codemirror-5.59.2/addon/fold/foldcode.js?' . $update_staic . '"></script>' . "\n";
    echo '<script src="/js/codemirror-5.59.2/addon/fold/foldgutter.js?' . $update_staic . '"></script>' . "\n";
    echo '<script src="/js/codemirror-5.59.2/addon/fold/brace-fold.js?' . $update_staic . '"></script>' . "\n";
    echo '<script src="/js/codemirror-5.59.2/addon/fold/xml-fold.js?' . $update_staic . '"></script>' . "\n";
    echo '<script src="/js/codemirror-5.59.2/mode/css/css.js"></script>' . "\n";
    echo '<script src="/js/codemirror-5.59.2/mode/javascript/javascript.js"></script>' . "\n";
    echo '<script src="/js/codemirror-5.59.2/mode/xml/xml.js"></script>' . "\n";
    echo '<script src="/js/codemirror-5.59.2/mode/htmlmixed/htmlmixed.js"></script>' . "\n";
    echo '<script src="/js/codemirror-5.59.2/addon/search/search_box.js?' . $update_staic . '"></script>' . "\n";
    echo '<link rel="stylesheet" href="/js/codemirror-5.59.2/addon/search/dialog_box.css?' . $update_staic . '">' . "\n";
    echo '<script src="/js/codemirror-5.59.2/addon/search/dialog_box.js?' . $update_staic . '"></script>' . "\n";
    echo '<script src="/js/codemirror-5.59.2/addon/search/searchcursor.js"></script>' . "\n";
    echo '<script src="/js/codemirror-5.59.2/addon/display/panel.js?' . $update_staic . '"></script>' . "\n";
}

include(Root . "/include/plugin/simple_html_dom.php");


// ============================================================
// هندهلر AJAX: ذخیره فرم اصلی (edit_custom)
// ============================================================
if ($_REQUEST['edit_custom']) {
    $like_or_star = ($_REQUEST['custom_block']);
    $custom_file = $_FILES['custom_file'];

    $row = db_query("SELECT * FROM $Tbl_Web WHERE id = ? LIMIT 1", [$Blog_id], 'first');
    $themeid = $row['themeid'];
    db_query("UPDATE blogs SET themeid = '0', theme2id = '0' WHERE id = ? LIMIT 1", [$Blog_id]);
    save_header_footer($themeid);
    $theme          = h_decode(get_themes('home'));
    $theme2         = h_decode(get_themes('more'));
    $template_cat   = h_decode(get_themes('cat'));
    $template_panel = h_decode(get_themes('panel'));

    $header   = int_($_REQUEST['header']);
    $sidebar  = int_($_REQUEST['sidebar']);
    $sidebar2 = int_($_REQUEST['sidebar2']);
    $footer   = int_($_REQUEST['footer']);
    $panel    = int_($_REQUEST['panel']);
    $more     = int_($_REQUEST['more']);
    $cat      = int_($_REQUEST['cat']);

    // تعیین نوع قالب
    $save_type = 'home';
    $is_set = '';

    if (strstr($theme, "[RB:Header]")   and $header)  { $theme = theme_get("header.1");  $is_set = 'header.1'; $save_type = 'header'; }
    if (strpos($theme, "[RB:Sidebar]")  and $sidebar) { $theme = theme_get("sidebar.1"); $is_set = 'sidebar.1'; $save_type = 'sidebar'; }
    if (strpos($theme, "[RB:Sidebar2]") and $sidebar2){ $theme = theme_get("sidebar.2"); $is_set = 'sidebar.2'; $save_type = 'sidebar2'; }
    if (strpos($theme, "[RB:Footer]")   and $footer)  { $theme = theme_get("footer.1");  $is_set = 'footer.1'; $save_type = 'footer'; }
    if ($panel) { $theme = $template_panel; $is_set = 'tem_panel.1'; $save_type = 'panel'; }
    if ($more)  { $theme = $theme2;         $save_type = 'more'; }
    if ($cat)   { $theme = $template_cat;   $save_type = 'cat'; }

    // ----- آپلود فایل‌ها -----
    if (is_array($custom_file)) {
        $config = [
            'api_url' => 'http://rozup.ir/panel.php?upload_rozblog=1',
            'blogid' => $weblog ?? '',
            'blog_id' => $Blog_id ?? 0,
            'pass' => $pass ?? '',
            'allowed_extensions' => ['gif','jpg','jpeg','png','bmp','webp','svg'],
            'overwrite' => true,
            'dir_id' => $dir_id ?? 0,
            'random_name' => false,
            'apply_watermark' => false,
            'create_thumbnails' => false,
            'max_size' => null,
            'folder_name' => null,
            'auto_create_folder' => false,
            'froala' => false,
            'editor' => 0,
            'metadata' => ['page' => 'custom_block']
        ];

        $value_array = [];
        try {
            $fileManager = new RozblogFileManager($config['api_url']);
            $fileManager->setAuth($config['blogid'], $config['blog_id'], $config['pass']);

            if (isset($_FILES['custom_file']['name']) && is_array($_FILES['custom_file']['name'])) {
                foreach ($_FILES['custom_file']['name'] as $key_block => $files) {
                    foreach ($files as $start_after => $fileName) {
                        if (!empty($fileName) && $_FILES['custom_file']['error'][$key_block][$start_after] == 0) {
                            $_FILES['temp_file'] = [
                                'name' => $_FILES['custom_file']['name'][$key_block][$start_after],
                                'type' => $_FILES['custom_file']['type'][$key_block][$start_after],
                                'tmp_name' => $_FILES['custom_file']['tmp_name'][$key_block][$start_after],
                                'error' => $_FILES['custom_file']['error'][$key_block][$start_after],
                                'size' => $_FILES['custom_file']['size'][$key_block][$start_after]
                            ];
                            $result = $fileManager->upload($config);
                            if ($result['success'] && !empty($result['data']['url'])) {
                                $value_array[$key_block][$start_after] = $result['data']['url'];
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $value_array = [];
        }
    }

    $block_active = $_REQUEST['block_active'];
    $list_res = [];

    foreach ($like_or_star as $key => $val) {
        $key_f = $key;

        if (check_array($value_array) && isset($value_array[$key_f])) {
            foreach ($value_array[$key_f] as $key5 => $val5) {
                $val[$key5] = $val5;
                $list_res[] = array('id' => $key_f, 'val' => $val5, 'field_idx' => $key5);
            }
        }

        $insert0 = '';
        $insert1 = '';
        $content = '';
        $Custom_Option_Block = between("<RB:CB=" . $key . ">", "</RB:CB>", $theme);
        $insert0 .= $Custom_Option_Block;

        if (strstr($insert0, "<RB:CBA")) {
            if ($block_active[$key]) {
                $insert0 = str_replace("<RB:CBA=2>", "<RB:CBA=1>", $insert0);
            } else {
                $insert0 = str_replace("<RB:CBA=1>", "<RB:CBA=2>", $insert0);
            }
        }

        if (check_array($val)) {
            $insert0 = update_cbc($insert0);
        } else {
            $Custom_Option_Content = between("<RB:Custom_Option_Content>", "</RB:Custom_Option_Content>", $insert0);
            $insert0 = str_replace($Custom_Option_Content, "", $insert0);
            $insert0 = str_replace("</RB:Custom_Option_Content>", $val . "</RB:Custom_Option_Content>", $insert0);
            $insert0 = str_replace("[roz]", $val, $insert0);
        }

        $theme = str_replace("<RB:CB=" . $key . ">" . $Custom_Option_Block . "</RB:CB>", "<RB:CB=" . $key . ">" . $insert0 . "</RB:CB>", $theme);
        $theme = theme_replace($theme);
    }

    // ============================================================
    // ذخیره بر اساس نوع قالب
    // ============================================================
    if ($save_type === 'more') {
        // ذخیره در قالب ادامه مطلب
        db_query("UPDATE blogs SET theme2id = '0' WHERE usern = ? LIMIT 1", [$Blog_id], 'raw');
        if (function_exists('blogs_theme_db')) {
            blogs_theme_db("", $theme);
        }
        theme_save_file($theme, ".temB");
    } elseif ($save_type === 'cat') {
        // ذخیره در فایل tem_cat.1
        $path = Root_Tem . "tem_cat.1";
        @file_put_contents($path, $theme);
    } elseif ($save_type === 'header') {
        theme_save($theme, 'header.1');
    } elseif ($save_type === 'footer') {
        theme_save($theme, 'footer.1');
    } elseif ($save_type === 'sidebar') {
        theme_save($theme, 'sidebar.1');
    } elseif ($save_type === 'sidebar2') {
        theme_save($theme, 'sidebar.2');
    } elseif ($save_type === 'panel') {
        theme_save($theme, 'tem_panel.1');
    } else {
        // home
        if ($row['them2s'] == 'on') {
            // برای theme2 (more) اگر فعاله
            if (function_exists('blogs_theme_db')) {
                blogs_theme_db($theme, $theme2);
            }
            theme_save_file($theme, ".temA");
            theme_save_file($theme2, ".temB");
        } else {
            if (!$is_set) save_db_theme($row['them2s'], $theme, $theme2);
            else theme_save_file($theme, ".temA");
        }
    }

    echo j_e(array('message' => $_langs['1wI56040'], 'list_res' => $list_res));
    die();
}


// ============================================================
// هندهلر AJAX: افزودن آیتم CBI
// ============================================================
if ($_REQUEST['add_cbi_item']) {
    $key_block = int_($_REQUEST['add_cbi_item']);
    $item_idx  = int_($_REQUEST['item_idx']);

    $row = db_query("SELECT * FROM $Tbl_Web WHERE id = ? LIMIT 1", [$Blog_id], 'first');
    $themeid = $row['themeid'];
    db_query("UPDATE blogs SET themeid = '0', theme2id = '0' WHERE id = ? LIMIT 1", [$Blog_id]);
    save_header_footer($themeid);

    $theme  = h_decode(get_themes('home'));
    $theme2 = h_decode(get_themes('more'));
    $template_cat   = h_decode(get_themes('cat'));
    $template_panel = h_decode(get_themes('panel'));

    $header   = int_($_REQUEST['header']);
    $sidebar  = int_($_REQUEST['sidebar']);
    $sidebar2 = int_($_REQUEST['sidebar2']);
    $footer   = int_($_REQUEST['footer']);
    $panel    = int_($_REQUEST['panel']);
    $more     = int_($_REQUEST['more']);
    $cat      = int_($_REQUEST['cat']);

    $is_set = '';
    if (strstr($theme, "[RB:Header]")   and $header)  { $theme = theme_get("header.1");  $is_set = 'header.1'; }
    if (strpos($theme, "[RB:Sidebar]")  and $sidebar) { $theme = theme_get("sidebar.1"); $is_set = 'sidebar.1'; }
    if (strpos($theme, "[RB:Sidebar2]") and $sidebar2){ $theme = theme_get("sidebar.2"); $is_set = 'sidebar.2'; }
    if (strpos($theme, "[RB:Footer]")   and $footer)  { $theme = theme_get("footer.1");  $is_set = 'footer.1'; }
    if ($panel) { $theme = $template_panel; $is_set = 'tem_panel.1'; }
    if ($more)  { $theme = $theme2;         $is_set = 'more.1'; }
    if ($cat)   { $theme = $template_cat;   $is_set = 'cat.1'; }

    $Custom_Option_Block = between("<RB:CB=" . $key_block . ">", "</RB:CB>", $theme);
    if (!$Custom_Option_Block) {
        die(json_encode(['success' => false, 'message' => 'بلوک یافت نشد'], JSON_UNESCAPED_UNICODE));
    }

    if (!preg_match_all('/<RB:CBI>.*?<\/RB:CBI>/is', $Custom_Option_Block, $cbi_matches, PREG_OFFSET_CAPTURE)) {
        die(json_encode(['success' => false, 'message' => 'آیتمی وجود ندارد'], JSON_UNESCAPED_UNICODE));
    }

    $total_items = count($cbi_matches[0]);
    if ($item_idx < 0 || $item_idx >= $total_items) {
        die(json_encode(['success' => false, 'message' => 'شماره آیتم نامعتبر'], JSON_UNESCAPED_UNICODE));
    }

    $source_item = $cbi_matches[0][$item_idx][0];
    $source_pos  = $cbi_matches[0][$item_idx][1];
    $source_end  = $source_pos + strlen($source_item);

    $new_block = substr($Custom_Option_Block, 0, $source_end)
               . $source_item
               . substr($Custom_Option_Block, $source_end);

    $theme = str_replace(
        "<RB:CB=" . $key_block . ">" . $Custom_Option_Block . "</RB:CB>",
        "<RB:CB=" . $key_block . ">" . $new_block . "</RB:CB>",
        $theme
    );
    $theme = theme_replace($theme);
    if ($is_set) theme_save($theme, $is_set);
    else
	{
		theme_save_file($theme, ".temA");
	}

    if ($row['them2s'] == 'on' && !$is_set) 
	{
        $Custom_Option_Block2 = between("<RB:CB=" . $key_block . ">", "</RB:CB>", $theme2);
        if ($Custom_Option_Block2) {
            if (preg_match_all('/<RB:CBI>.*?<\/RB:CBI>/is', $Custom_Option_Block2, $cbi2, PREG_OFFSET_CAPTURE)) 
			{
                if (isset($cbi2[0][$item_idx])) {
                    $src2 = $cbi2[0][$item_idx][0];
                    $pos2 = $cbi2[0][$item_idx][1];
                    $end2 = $pos2 + strlen($src2);
                    $new_block2 = substr($Custom_Option_Block2, 0, $end2)
                                . $src2
                                . substr($Custom_Option_Block2, $end2);
                    $theme2 = str_replace(
                        "<RB:CB=" . $key_block . ">" . $Custom_Option_Block2 . "</RB:CB>",
                        "<RB:CB=" . $key_block . ">" . $new_block2 . "</RB:CB>",
                        $theme2
                    );
                    $theme2 = theme_replace($theme2);
                    theme_save_file($theme2, ".temB");
                }
            }
        }
    }

    global $pattern_contetnt;
    $new_idx = $item_idx + 1;
    $total_after = $total_items + 1;
    $new_item_html = rb_render_cbi_item($key_block, $new_idx, $total_after, $source_item, "انتخاب عکس", $pattern_contetnt);

    die(json_encode([
        'success' => true,
        'message' => 'آیتم اضافه شد',
        'html' => $new_item_html,
        'new_index' => $new_idx,
        'total' => $total_after
    ], JSON_UNESCAPED_UNICODE));
}


// ============================================================
// هندهلر AJAX: حذف آیتم CBI
// ============================================================
if ($_REQUEST['delete_cbi_item']) {
    $key_block = int_($_REQUEST['delete_cbi_item']);
    $item_idx  = int_($_REQUEST['item_idx']);

    $row = db_query("SELECT * FROM $Tbl_Web WHERE id = ? LIMIT 1", [$Blog_id], 'first');
    $themeid = $row['themeid'];
    db_query("UPDATE blogs SET themeid = '0', theme2id = '0' WHERE id = ? LIMIT 1", [$Blog_id]);
    save_header_footer($themeid);

    $theme  = h_decode(get_themes('home'));
    $theme2 = h_decode(get_themes('more'));
    $template_cat   = h_decode(get_themes('cat'));
    $template_panel = h_decode(get_themes('panel'));

    $header   = int_($_REQUEST['header']);
    $sidebar  = int_($_REQUEST['sidebar']);
    $sidebar2 = int_($_REQUEST['sidebar2']);
    $footer   = int_($_REQUEST['footer']);
    $panel    = int_($_REQUEST['panel']);
    $more     = int_($_REQUEST['more']);
    $cat      = int_($_REQUEST['cat']);

    $is_set = '';
    if (strstr($theme, "[RB:Header]")   and $header)  { $theme = theme_get("header.1");  $is_set = 'header.1'; }
    if (strpos($theme, "[RB:Sidebar]")  and $sidebar) { $theme = theme_get("sidebar.1"); $is_set = 'sidebar.1'; }
    if (strpos($theme, "[RB:Sidebar2]") and $sidebar2){ $theme = theme_get("sidebar.2"); $is_set = 'sidebar.2'; }
    if (strpos($theme, "[RB:Footer]")   and $footer)  { $theme = theme_get("footer.1");  $is_set = 'footer.1'; }
    if ($panel) { $theme = $template_panel; $is_set = 'tem_panel.1'; }
    if ($more)  { $theme = $theme2;         $is_set = 'more.1'; }
    if ($cat)   { $theme = $template_cat;   $is_set = 'cat.1'; }

    $Custom_Option_Block = between("<RB:CB=" . $key_block . ">", "</RB:CB>", $theme);
    if (!$Custom_Option_Block) {
        die(json_encode(['success' => false, 'message' => 'بلوک یافت نشد'], JSON_UNESCAPED_UNICODE));
    }

    if (!preg_match_all('/<RB:CBI>.*?<\/RB:CBI>/is', $Custom_Option_Block, $cbi_matches, PREG_OFFSET_CAPTURE)) {
        die(json_encode(['success' => false, 'message' => 'آیتمی وجود ندارد'], JSON_UNESCAPED_UNICODE));
    }

    $total_items = count($cbi_matches[0]);
    if ($total_items <= 1) {
        die(json_encode(['success' => false, 'message' => 'حداقل یک آیتم باید باقی بماند'], JSON_UNESCAPED_UNICODE));
    }
    if ($item_idx < 0 || $item_idx >= $total_items) {
        die(json_encode(['success' => false, 'message' => 'شماره آیتم نامعتبر'], JSON_UNESCAPED_UNICODE));
    }

    $source_item = $cbi_matches[0][$item_idx][0];
    $source_pos  = $cbi_matches[0][$item_idx][1];

    $new_block = substr($Custom_Option_Block, 0, $source_pos)
               . substr($Custom_Option_Block, $source_pos + strlen($source_item));

    $theme = str_replace(
        "<RB:CB=" . $key_block . ">" . $Custom_Option_Block . "</RB:CB>",
        "<RB:CB=" . $key_block . ">" . $new_block . "</RB:CB>",
        $theme
    );
    $theme = theme_replace($theme);
    if ($is_set) theme_save($theme, $is_set);
    else theme_save_file($theme, ".temA");

    if ($row['them2s'] == 'on' && !$is_set) {
        $Custom_Option_Block2 = between("<RB:CB=" . $key_block . ">", "</RB:CB>", $theme2);
        if ($Custom_Option_Block2) {
            if (preg_match_all('/<RB:CBI>.*?<\/RB:CBI>/is', $Custom_Option_Block2, $cbi2, PREG_OFFSET_CAPTURE)) {
                if (count($cbi2[0]) > 1 && isset($cbi2[0][$item_idx])) {
                    $src2 = $cbi2[0][$item_idx][0];
                    $pos2 = $cbi2[0][$item_idx][1];
                    $new_block2 = substr($Custom_Option_Block2, 0, $pos2)
                                . substr($Custom_Option_Block2, $pos2 + strlen($src2));
                    $theme2 = str_replace(
                        "<RB:CB=" . $key_block . ">" . $Custom_Option_Block2 . "</RB:CB>",
                        "<RB:CB=" . $key_block . ">" . $new_block2 . "</RB:CB>",
                        $theme2
                    );
                    $theme2 = theme_replace($theme2);
                    theme_save_file($theme2, ".temB");
                }
            }
        }
    }

    die(json_encode([
        'success' => true,
        'message' => 'آیتم حذف شد',
        'total' => $total_items - 1
    ], JSON_UNESCAPED_UNICODE));
}



// ============================================================
// نمایش فرم‌ها
// ============================================================
$theme          = h_decode(get_themes('home'));
$theme_more     = h_decode(get_themes('more'));
$theme_cat      = h_decode(get_themes('cat'));
$template_panel = h_decode(get_themes('panel'));

list($get_header, $get_footer, $get_sidebar, $get_sidebar2) = get_header_footer_themes_blogs();

// ============================================================
// چک شناسه‌های تکراری RB:CB در همه بخش‌ها
// ============================================================
$all_themes_for_check = [
    'صفحه اصلی'  => $theme,
    'ادامه مطلب' => $theme_more,
    'موضوعات'    => $theme_cat,
    'هدر'        => $get_header,
    'سایدبار'    => $get_sidebar,
    'سایدبار۲'   => $get_sidebar2,
    'فوتر'       => $get_footer,
    'پنل'        => $template_panel,
];

$all_keys_with_section = [];
foreach ($all_themes_for_check as $section_name => $section_theme) {
    if (empty($section_theme)) continue;

    preg_match_all('/<RB:CB=([A-Za-z0-9_]+)>/i', $section_theme, $cb_keys);

    foreach ($cb_keys[1] as $one_key) {
        if (!isset($all_keys_with_section[$one_key])) {
            $all_keys_with_section[$one_key] = [];
        }
        if (!in_array($section_name, $all_keys_with_section[$one_key])) {
            $all_keys_with_section[$one_key][] = $section_name;
        }
    }
}

$all_duplicates = [];
foreach ($all_keys_with_section as $k => $sections) {
    if (count($sections) > 1) {
        $all_duplicates[$k] = $sections;
    }
}

if (!empty($all_duplicates))
{
    $msg_parts = [];
    foreach ($all_duplicates as $k => $sections) {
        $msg_parts[] = "شناسه <b>" . htmlspecialchars($k) . "</b> در بخش‌های " . implode('، ', $sections) . " تکراری است";
    }

    echo '<div class="rzb-btn rzb-md rzb-danger rzb-full" style="display:block;margin:10px 0;padding:12px 18px;border-radius:8px;direction:rtl;font-family:tahoma;text-align:right;background:#dc3545;color:#fff;">';
    echo '⚠️ <b>هشدار شناسه تکراری:</b><br>';
    echo implode('<br>', $msg_parts);
    echo '<br><span style="font-size:12px;opacity:0.85;">لطفاً شناسه‌ها را در کد قالب اصلاح کنید تا از بروز مشکل در ویرایش جلوگیری شود.</span>';
    echo '</div>';
}

$code_ed = 1;






function show_custom_fields($theme, $type = "")
{
    global $phf, $pattern_contetnt, $_langs, $code_ed;

    $phf = 0;

    preg_match_all("/<RB:CB=([A-Za-z0-9_]+)>(.*?)<\/RB:CB>/is", $theme, $item);
    $theme = theme_replace($theme);

    if (empty($item[0])) {
        return $phf;
    }

	// ============================================================
	// نوار جستجو و فیلتر — فقط یک بار در اولین بخش نمایش داده می‌شه
	// ============================================================
	global $cb_search_bar_shown;
	if (empty($cb_search_bar_shown)) {
		$cb_search_bar_shown = true;
	?>
	<div id="cb_search_bar" style="margin:15px 10px 10px 10px;direction:rtl;background:#f8f9fa;padding:12px 15px;border-radius:8px;border:1px solid #e0e0e0;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
		<span style="font-weight:bold;color:#555;margin-left:5px;">جستجو:</span>
		<input type="text" id="cb_search_input" placeholder="شناسه یا عنوان بلوک..." 
			   style="padding:8px 12px;border-radius:6px;border:1px solid #ccc;width:220px;font-family:tahoma;font-size:13px;">
		<select id="cb_filter_section" style="padding:8px 12px;border-radius:6px;border:1px solid #ccc;font-family:tahoma;font-size:13px;">
			<option value="">همه قالب‌ها</option>
			<option value="صفحه اصلی">صفحه اصلی</option>
			<option value="ادامه مطلب">ادامه مطلب</option>
			<option value="موضوعات">موضوعات</option>
			<option value="هدر">هدر</option>
			<option value="فوتر">فوتر</option>
			<option value="سایدبار">سایدبار</option>
			<option value="سایدبار ۲">سایدبار ۲</option>
			<option value="پنل کاربری">پنل کاربری</option>
		</select>
		<select id="cb_filter_cbi" style="padding:8px 12px;border-radius:6px;border:1px solid #ccc;font-family:tahoma;font-size:13px;">
			<option value="">همه بلوک‌ها</option>
			<option value="has_cbi">دارای آیتم</option>
			<option value="no_cbi">فقط ساده</option>
			<option value="has_file">دارای عکس</option>
			<option value="no_file">بدون عکس</option>
		</select>
		<button type="button" id="cb_clear_filters" class="btn btn-sm" style="padding:8px 14px;border-radius:6px;background:#6c757d;color:#fff;border:none;cursor:pointer;font-family:tahoma;font-size:12px;">پاک کردن فیلترها</button>
		<span id="cb_filter_count" style="color:#888;font-size:12px;margin-right:auto;"></span>
	</div>
	<?php
	}

    foreach ($item[2] as $key2 => $item2) {

        $key = $item[1][$key2];

        echo '	<form action="" method="post" enctype="multipart/form-data" class="cbi_main_form"><input type="hidden" name="edit_custom" value="1" />';

        if ($type == 'header') {
            echo '<input type="hidden" name="header" value="1" />';
        } elseif ($type == 'footer') {
            echo '<input type="hidden" name="footer" value="1" />';
        } elseif ($type == 'sidebar') {
            echo '<input type="hidden" name="sidebar" value="1" />';
        } elseif ($type == 'sidebar2') {
            echo '<input type="hidden" name="sidebar2" value="1" />';
        } elseif ($type == 'panel') {
            echo '<input type="hidden" name="panel" value="1" />';
        } elseif ($type == 'more') {
            echo '<input type="hidden" name="more" value="1" />';
        } elseif ($type == 'cat') {
            echo '<input type="hidden" name="cat" value="1" />';
        }

$title = s_i(between("<RB:Custom_Option_Title>", "</RB:Custom_Option_Title>", $item2));
if (!$title) $title = between("<RB:CBT=", ">", $item2);

// ============================================================
// نام فارسی قالب بر اساس $type
// ============================================================
$section_names = [
    ''         => 'صفحه اصلی',
    'home'     => 'صفحه اصلی',
    'more'     => 'ادامه مطلب',
    'cat'      => 'موضوعات',
    'header'   => 'هدر',
    'footer'   => 'فوتر',
    'sidebar'  => 'سایدبار',
    'sidebar2' => 'سایدبار ۲',
    'panel'    => 'پنل کاربری',
];
$section_fa = $section_names[$type] ?? 'نامشخص';

$section_colors = [
    'صفحه اصلی' => '#2196F3',
    'ادامه مطلب' => '#9C27B0',
    'موضوعات'    => '#FF9800',
    'هدر'        => '#4CAF50',
    'فوتر'       => '#795548',
    'سایدبار'    => '#00BCD4',
    'سایدبار ۲'  => '#00BCD4',
    'پنل کاربری' => '#F44336',
];
$section_color = $section_colors[$section_fa] ?? '#607D8B';

// برچسب قالب
$section_badge = '<span class="section_badge" style="background:' . $section_color . ';color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;margin-right:5px;">' . $section_fa . '</span>';

// لینک ویرایش قالب
$edit_links = [
    ''         => panel_sess.'edit_template',
    'home'     => panel_sess.'edit_template',
    'more'     => panel_sess.'edit_template?d=2',
    'cat'      => panel_sess.'edit_template?d=7',
    'header'   => panel_sess.'edit_template?d=6',
    'footer'   => panel_sess.'edit_template?d=6',
    'sidebar'  => panel_sess.'edit_template?d=6',
    'sidebar2' => panel_sess.'edit_template?d=6',
    'panel'    => panel_sess.'edit_template?d=8',
];

$edit_link = $edit_links[$type] ?? '';

if ($edit_link) {
    $section_badge .= '<a href="' . $edit_link . '" class="rzb-btn rzb-xs rzb-soft-success rzb-rnd" title="ویرایش قالب ' . $section_fa . '">ویرایش قالب</a>';
}

// اگه CBI داره، تعداد آیتم‌ها رو هم نشون بده
if (strstr($item2, "<RB:CBI")) 
{
    $cbi_count_for_badge = count(rb_get_cbi_items($item2));
    $section_badge .= '<span style="background:#607D8B;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;margin-right:5px;">' . $cbi_count_for_badge . ' آیتم</span>';
}

		
		
		
		
		
		
		
        if (!$title) $title = between("<RB:CBT=", ">", $item2);

        $title_btn_upload = between("<RB:CBTBU=", ">", $item2);
        if ($title_btn_upload) {
            $title_btn_upload = $title_btn_upload;
        } else {
            $title_btn_upload = "انتخاب عکس";
        }

        $description = s_i(between("<RB:CBD>", "</RB:CBD>", $item2));
        $description = str_replace(array("[br]", "[b]", "[/b]"), array("<br>", "<b>", "</b>"), $description);
        $description = preg_replace('/\[color\s*?=([^;]*?)\]/i', '<span style="color:$1;">', $description);
        $description = preg_replace('/\[size\s*?=([^;]*?)\]/i', '<span style="font-size:$1px;">', $description);
        $description = preg_replace("/\[\/(size|font|color)\]/i", '</span>', $description);

        $cbds_raw = between("<RB:CBDS=", ">", $item2);
        list($color, $border) = explode(":", $cbds_raw . ":");

        $block_active = "";
        $add_active = "";
        if (strstr($item2, "<RB:CBA")) {
            $block_active = between("<RB:CBA=", ">", $item2);
            if ($block_active) {
                $checked = $block_active == 1 ? "checked" : "";
                $add_active = '<input ' . $checked . ' class="input-switch" name="block_active[' . $key . ']" value="1" type="checkbox" id="block_active' . $key . '"/><label class="label-switch" for="block_active' . $key . '"></label><span class="info-text"></span>';
            }
        }

        $has_cbi = strstr($item2, "<RB:CBI");

        if (!strstr($item2, "<RB:CBC")) {
            $content = between("<RB:Custom_Option_Content>", "</RB:Custom_Option_Content>", $item2);
        } else {
            preg_match_all($pattern_contetnt, $item2, $content2);
            $typpe = $content2[2][0];
            $content = $content2[3][0];
        }

        if ($typpe or $content) {
?>
   <br>
   <div style="margin-right:10px;text-align:right;">
   <div class="ibox">

<!-- هدر بلوک -->
<div class="cb_block">
  <div class="cb_block_header">
    <div class="cb_block_header_right">
      <?= $section_badge ?>
      <span class="cbi_block_key">#<?= $key ?></span>
      <span class="cbi_block_title"><?= $title ?></span>
    </div>
    <div class="cb_block_header_left">
      <?= $add_active ?>
      <button type="button" class="cb_toggle_body" title="باز/بسته">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </button>
    </div>
  </div>
  <div class="cb_block_body">
  
  
  <div style="margin-left:5px;">
  <?php if ($description) echo message_help($description, s_i($color), s_i($border)); ?>

  <?php if ($typpe) { ?>
  <div class="div_form div_form_color" id="c_<?=$key?>">
  <?php

preg_match_all('/<RB:CBI>.*?<\/RB:CBI>/is', $item2, $cbi_matches, PREG_OFFSET_CAPTURE);
preg_match_all($pattern_contetnt, $item2, $all_cbc, PREG_OFFSET_CAPTURE);

$elements = [];

foreach ($cbi_matches[0] as $idx => $cbi) {
    $elements[] = ['type' => 'cbi', 'start' => $cbi[1], 'idx' => $idx, 'html' => $cbi[0]];
}

foreach ($all_cbc[0] as $i => $cbc) {
    $inside = false;
    foreach ($cbi_matches[0] as $cbi) {
        if ($cbc[1] >= $cbi[1] && $cbc[1] <= $cbi[1] + strlen($cbi[0])) { $inside = true; break; }
    }
    if (!$inside) {
        $elements[] = [
            'type'       => 'cbc',
            'start'      => $cbc[1],
            'title'      => $all_cbc[3][$i][0],   // ← [0] اضافه شد
            'field_type' => $all_cbc[5][$i][0],   // ← [0] اضافه شد
            'value'      => $all_cbc[6][$i][0],   // ← [0] اضافه شد
        ];
    }
}

usort($elements, function($a, $b) { return $a['start'] - $b['start']; });

$field_counter = 0;
$total_cbi = count($cbi_matches[0]);

foreach ($elements as $el) {
    if ($el['type'] === 'cbc') {
        echo rb_render_cbi_field($key, $field_counter, $el['field_type'], $el['title'], $el['value'], $title_btn_upload);
        $field_counter++;
    } else {
        preg_match_all($pattern_contetnt, $el['html'], $one_content);
        $field_count = count($one_content[6]);
        echo rb_render_cbi_item($key, $el['idx'], $total_cbi, $el['html'], $title_btn_upload, $pattern_contetnt, $field_counter);
        $field_counter += $field_count;
    }
}

  ?>
  <div id="add_item<?=$key?>"></div>
  </div>
  <?php
        } else {
  ?>
  <textarea class="resize_textbox" oninput="auto_grow(this)" style="width:98%;min-height:30px;max-height: 150px;" name="custom_block[<?=$key?>]" dir="auto"><?=$content?></textarea>
  <?php
        }
  ?>
  <div class="t_a_c">
  <?=$typpe ? "" : '<center class="m_t_15">' ?><input onclick="form_ajax(this.form,'?ajax=1');" class="rzb-btn rzb-lg rzb-primary" type="submit" value="<?=$_langs['DSRsC4hM'] /* ویرایش */ ?>"></center>
  </div>
  </div>


  </div><!-- /cb_block_body -->
</div><!-- /cb_block -->



  </div>
  </div>
   <?php
        }
        $phf++;

        echo '</form>';
    }

    return $phf;
}
?>
   <br>

<?php
// نمایش فرم‌های بخش‌های مختلف
$phf = 0;

if ($get_header)   { $header = 1;  $phf = show_custom_fields($get_header, 'header'); }
$phf = show_custom_fields($theme);
$phf = show_custom_fields($theme_more, 'more');
$phf = show_custom_fields($theme_cat, 'cat');
if ($get_sidebar)  { $sidebar = 1; $phf = show_custom_fields($get_sidebar, 'sidebar'); }
if ($get_sidebar2) { $sidebar2 = 1;$phf = show_custom_fields($get_sidebar2, 'sidebar2'); }
if ($get_footer)   { $footer = 1;  $phf = show_custom_fields($get_footer, 'footer'); }
$phf = show_custom_fields($template_panel, "panel");
?>

<div style="margin-top:500px;"></div>



<script>


$(document).on('click', '.cb_toggle_body', function () {
    $(this).closest('.cb_block').toggleClass('collapsed');
});

// ============================================================
// جستجو و فیلتر بلوک‌ها
// ============================================================
function apply_cb_filters() {
    var q = ($('#cb_search_input').val() || '').toLowerCase().trim();
    var section = $('#cb_filter_section').val() || '';
    var cbiFilter = $('#cb_filter_cbi').val() || '';
    var visibleCount = 0;
    var totalCount = 0;

    $('.cbi_main_form').each(function () {
        totalCount++;
        var form = $(this);

        // استخراج متن برای جستجو
        var key = form.find('.cbi_block_key').text().toLowerCase();
        var title = form.find('.cbi_block_title').text().toLowerCase();
        var sectionText = form.find('.section_badge').first().text().trim();

        // آیا CBI داره؟
        var hasCbi = form.find('.cbi_item_wrapper').length > 0;

        // آیا عکس داره؟ (فیلد file با مقدار غیر خالی)
        var hasFile = false;
        form.find('.cbi_field_file input[type="text"]').each(function () {
            var val = ($(this).val() || '').trim();
            if (val !== '' && val !== '#' && val !== 'http://' && val !== 'https://') {
                hasFile = true;
                return false; // break
            }
        });

        var matchQ = !q || key.indexOf(q) !== -1 || title.indexOf(q) !== -1;
        var matchSection = !section || sectionText.indexOf(section) !== -1;

        var matchCbi = true;
        if (cbiFilter === 'has_cbi') matchCbi = hasCbi;
        else if (cbiFilter === 'no_cbi') matchCbi = !hasCbi;
        else if (cbiFilter === 'has_file') matchCbi = hasFile;
        else if (cbiFilter === 'no_file') matchCbi = !hasFile;

        if (matchQ && matchSection && matchCbi) {
            form.show();
            visibleCount++;
        } else {
            form.hide();
        }
    });

    $('#cb_filter_count').text('نمایش ' + visibleCount + ' از ' + totalCount + ' بلوک');
}

$(document).on('input', '#cb_search_input', apply_cb_filters);
$(document).on('change', '#cb_filter_section', apply_cb_filters);
$(document).on('change', '#cb_filter_cbi', apply_cb_filters);
$(document).on('click', '#cb_clear_filters', function () {
    $('#cb_search_input').val('');
    $('#cb_filter_section').val('');
    $('#cb_filter_cbi').val('');
    apply_cb_filters();
});

// اجرای اولیه بعد از بارگذاری
$(document).ready(function () {
    if ($('#cb_search_bar').length) {
        apply_cb_filters();
    }
});



// ============================================================
// تابع کمکی: شماره‌گذاری مجدد همه آیتم‌های CBI در یک بلوک
// ============================================================
function reindex_cbi_items(blockId) {
    var items = $('.cbi_item_wrapper[data-block="' + blockId + '"]');
    var total = items.length;
    var fieldCounter = 0;

    items.each(function (i) {
        var w = $(this);
        w.attr('data-index', i);

        w.find('.cbi_item_header span').first().text('آیتم ' + (i + 1));
        w.find('.cbi_add').attr('data-index', i);
        w.find('.cbi_delete').attr('data-index', i);

        w.find('input, textarea, select').each(function () {
            var el = $(this);
            var nameAttr = el.attr('name');
            if (!nameAttr) return;

            var match = nameAttr.match(/^(custom_block|custom_file)\[(\d+)\]\[\d+\]$/);
            if (match) {
                el.attr('name', match[1] + '[' + blockId + '][' + fieldCounter + ']');
                fieldCounter++;
            }
        });

        if (total > 1) {
            if (w.find('.cbi_delete').length === 0) {
                w.find('.cbi_item_actions').append(
                    '<button type="button" class="btn danger cbi_delete" data-block="' + blockId + '" data-index="' + i + '">حذف</button>'
                );
            }
        } else {
            w.find('.cbi_delete').remove();
        }
    });
}

// ============================================================
// AJAX: افزودن آیتم CBI
// ============================================================
$(document).on('click', '.cbi_add', function (e) {
    e.preventDefault();
    var btn = $(this);
    var blockId = btn.data('block');
    var itemIdx = btn.data('index');
    var wrapper = btn.closest('.cbi_item_wrapper');
    var form = btn.closest('form');

    btn.prop('disabled', true).text('در حال افزودن...');

    var extraData = '';
    form.find('input[type=hidden]').each(function () {
        if (this.name && this.name !== 'edit_custom' && this.name.indexOf('block_active') !== 0) {
            extraData += '&' + encodeURIComponent(this.name) + '=' + encodeURIComponent(this.value);
        }
    });

    var url = '?ajax=1&add_cbi_item=' + blockId + '&item_idx=' + itemIdx + extraData;

    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function (res) {
            btn.prop('disabled', false).text('افزودن');
            if (!res || !res.success) {
                alert((res && res.message) ? res.message : 'خطا در افزودن آیتم');
                return;
            }

            wrapper.after(res.html);
            reindex_cbi_items(blockId);
        },
        error: function () {
            btn.prop('disabled', false).text('افزودن');
            alert('خطا در ارتباط با سرور');
        }
    });
});


// ============================================================
// AJAX: حذف آیتم CBI
// ============================================================
$(document).on('click', '.cbi_delete', function (e) {
    e.preventDefault();
    var btn = $(this);
    var blockId = btn.data('block');
    var itemIdx = btn.data('index');
    var wrapper = btn.closest('.cbi_item_wrapper');
    var form = btn.closest('form');

    if (!confirm('آیا از حذف این آیتم مطمئن هستید؟')) return;

    btn.prop('disabled', true).text('در حال حذف...');

    var extraData = '';
    form.find('input[type=hidden]').each(function () {
        if (this.name && this.name !== 'edit_custom' && this.name.indexOf('block_active') !== 0) {
            extraData += '&' + encodeURIComponent(this.name) + '=' + encodeURIComponent(this.value);
        }
    });

    var url = '?ajax=1&delete_cbi_item=' + blockId + '&item_idx=' + itemIdx + extraData;

    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function (res) {
            if (!res || !res.success) {
                btn.prop('disabled', false).text('حذف');
                alert((res && res.message) ? res.message : 'خطا در حذف آیتم');
                return;
            }

            wrapper.fadeOut(150, function () {
                $(this).remove();
                reindex_cbi_items(blockId);
            });
        },
        error: function () {
            btn.prop('disabled', false).text('حذف');
            alert('خطا در ارتباط با سرور');
        }
    });
});


function setact3() {
    dynaframe();
    dynaframe();
}

function setact2() {
    setact3();
    setTimeout(function () {
        dynaframe();
    }, 2000);
}
setact2();
</script>








<script>var enable_Editor = 1;var height_editor = 200;</script>
<script src="/js/codemirror_include.js?2.2"></script>

<style>textarea img{max-width:80%;}.mce-content-body img{max-width:80%;}.mce-content-body{font:15px Vazir!important;}</style>
<script type="text/javascript" src="/editor/tinymce_6/tinymce.min.js?2" referrerpolicy="origin"></script>
<script>
tinymce.init({
selector: 'textarea.editor_field',
language: 'fa_IR',
skin: 'oxide-dark',
plugins: 'quickbars directionality preview importcss searchreplace visualblocks visualchars fullscreen image link media template codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap emoticons code',
toolbar1: 'undo redo bold italic underline strikethrough fontsize fontfamily blocks pre',
toolbar2: 'alignleft aligncenter alignright alignjustify | outdent indent numlist bullist | forecolor backcolor removeformat blockquote',
toolbar3: 'insertfile image media table link unlink  codesample | charmap emoticons | fullscreen  preview code | a11ycheck rtl ltr| showcomments addcomment anchor',
content_style: "body { font-family:tahoma,Arial,sans-serif; font-size:12px }",
contextmenu: false,
content_style: 'img {max-width: 95%;}',
branding: false,
valid_children: "+*[*]",
valid_elements: "*[*]",
force_br_newlines : true,
height : '480'
});
</script>

<style>
.div_2 { margin-bottom: 0rem; margin-top: 10px; }
.CodeMirror-find-and-replace-dialog { display: none; }
.CodeMirror, .rb_codemirror { border: 1px solid #eee; }
.cbi_item_wrapper { background: #fafafa; transition: background 0.2s; }
.cbi_item_wrapper:hover { background: #f0f8ff; }
.cbi_add, .cbi_delete { padding: 4px 12px !important; font-size: 12px !important; cursor: pointer; }

#cb_search_bar select:focus,
#cb_search_bar input:focus {
    outline: none;
    border-color: #2196F3;
    box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
}

#cb_search_bar .btn:hover {
    opacity: 0.9;
}

.cb_edit_theme_link:hover {
    background: #2196F3;
    color: #fff !important;
}

.cbi_main_form {
    transition: opacity 0.2s;
}





/* ===== بلوک ===== */
.cb_block {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    overflow: hidden;
    border: 1px solid #eaeaea;
    transition: box-shadow 0.2s;
}

.cb_block:hover {
    box-shadow: 0 4px 18px rgba(0,0,0,0.10);
}

/* ===== هدر ===== */
.cb_block_header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 16px;
    background: linear-gradient(135deg, #f8f9fb 0%, #eef1f6 100%);
    border-bottom: 1px solid #eaeaea;
    flex-wrap: wrap;
}

.cb_block_header_right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1 1 auto;
    min-width: 0;
    flex-wrap: wrap;
}

.cb_block_header_left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 0 0 auto;
}

.cbi_block_key {
    background: #2c3e50;
    color: #fff;
    font-size: 11px;
    padding: 3px 9px;
    border-radius: 20px;
    font-weight: bold;
    letter-spacing: 0.3px;
    direction: ltr;
}

.cbi_block_title {
    font-size: 14px;
    font-weight: 600;
    color: #2c3e50;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 320px;
}

.section_badge {
    font-size: 11px !important;
    padding: 3px 10px !important;
    border-radius: 20px !important;
    font-weight: 600;
}

/* دکمه باز/بسته */
.cb_toggle_body {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: none;
    background: #e4e9f0;
    color: #555;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.cb_toggle_body:hover {
    background: #d1d9e6;
    transform: scale(1.05);
}

.cb_block_body {
    padding: 14px 16px;
    transition: max-height 0.3s ease;
}

.cb_block.collapsed .cb_block_body {
    display: none;
}

.cb_block.collapsed .cb_toggle_body svg {
    transform: rotate(-90deg);
}

.cb_block_desc {
    background: #f1f7ff;
    border-right: 3px solid #2196F3;
    padding: 10px 14px;
    margin-bottom: 12px;
    border-radius: 6px;
    font-size: 12px;
    color: #555;
    line-height: 1.7;
}

/* ===== فوتر ===== */
.cb_block_footer {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px dashed #e0e0e0;
}

/* ===== ریسپانسیو موبایل ===== */
@media (max-width: 768px) {
    .cb_block {
        margin: 10px 6px;
        border-radius: 10px;
    }

    .cb_block_header {
        padding: 10px 12px;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }

    .cb_block_header_right {
        justify-content: flex-start;
        width: 100%;
    }

    .cb_block_header_left {
        justify-content: space-between;
        width: 100%;
    }

    .cbi_block_title {
        max-width: 100%;
        white-space: normal;
        word-break: break-word;
        font-size: 13px;
    }

    .cb_block_body {
        padding: 12px;
    }
}








/* دکمه‌های هدر آیتم CBI */
.cbi_item_header_actions {
    display: flex;
    gap: 6px;
    align-items: center;
}

.cbi_add,
.cbi_delete {
    width: 28px;
    height: 28px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
    padding: 0;
}

.cbi_add {
    background: #e8f5e9;
    color: #2e7d32;
}

.cbi_add:hover {
    background: #2e7d32;
    color: #fff;
    transform: scale(1.08);
}

.cbi_delete {
    background: #ffebee;
    color: #c62828;
}

.cbi_delete:hover {
    background: #c62828;
    color: #fff;
    transform: scale(1.08);
}

.cbi_add:active,
.cbi_delete:active {
    transform: scale(0.95);
}
</style>