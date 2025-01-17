<?php
/**
 * 将html代码转短代码实现html音乐播放器显示，并保证markdown代码解析不被干扰。
 * 
 * @package MusicsPlayer 
 * @author HansJackTop
 * @version 1.0.0
 * @dependence 9.9.2-*
 * @link https://read.hansjack.top/
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class MusicPlayer_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->bottom = array('MusicPlayer_Plugin', 'insertButton');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('MusicPlayer_Plugin', 'parseContent');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('MusicPlayer_Plugin', 'parseContent');
    }

    public static function deactivate()
    {
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    public static function insertButton($post)
    {
        echo '<button type="button" onclick="insertMusicPlayer()">插入音乐播放器</button>';
    }

    public static function parseContent($content, $widget)
    {
        // 正则解析 {music img="封面图片地址" audio="音频地址" lyr="歌词文件链接"}
        $pattern = '/\{music\s+img="(.*?)"\s+audio="(.*?)"\s+lyr="(.*?)"\}/i';
        $replacement = function ($matches) {
            $img = $matches[1];
            $audio = $matches[2];
            $lyr = $matches[3];

            return self::generateMusicPlayer($img, $audio, $lyr);
        };

        // 替换 {music} 标签
        return preg_replace_callback($pattern, $replacement, $content);
    }

    public static function generateMusicPlayer($img, $audio, $lyr)
    {
        return <<<HTML
        <div style="position: relative; width: 100%; max-width: 800px; margin: 0 auto; padding: 10px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); background-color: #f8f8f8; text-align: center;">
            <div style="margin-bottom: 15px;">
                <img src="{$img}" alt="专辑封面" style="width: 100%; max-width: 300px; height: auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);">
            </div>
            <audio id="audio" controls style="width: 100%; margin-bottom: 15px;">
                <source src="{$audio}" type="audio/mp3">
                您的浏览器不支持音频播放，请升级到支持 HTML5 的浏览器。
            </audio>
            <div id="lyrics" style="max-height: 600px; overflow-y: auto; font-size: 14px; line-height: 1.6; color: #333; background-color: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    var lyrUrl = `{$lyr}`;
                    var lyricsContainer = document.getElementById("lyrics");
                    var audio = document.getElementById("audio");

                    fetchLyrics(lyrUrl, function (lyrics) {
                        var lyricLines = parseLyrics(lyrics);
                        renderLyrics(lyricLines, lyricsContainer);

                        audio.ontimeupdate = function () {
                            var currentTime = audio.currentTime;
                            updateLyrics(currentTime, lyricLines);
                        };
                    });
                });

                function fetchLyrics(url, callback) {
                    fetch(url)
                        .then(response => response.text())
                        .then(data => callback(data))
                        .catch(error => {
                            console.error('歌词加载失败:', error);
                        });
                }

                function parseLyrics(lyricsText) {
                    var lines = lyricsText.split("\\n");
                    var parsedLyrics = lines.map(function (line) {
                        var match = line.match(/^\\[(\\d{2}):(\\d{2}\\.\\d+)\\](.*)$/);
                        if (match) {
                            return {
                                time: parseFloat(match[1]) * 60 + parseFloat(match[2]),
                                text: match[3].trim()
                            };
                        }
                        return null;
                    }).filter(function (line) {
                        return line !== null;
                    });
                    return parsedLyrics;
                }

                function renderLyrics(lyricLines, container) {
                    lyricLines.forEach(function (line) {
                        var p = document.createElement("p");
                        p.setAttribute("data-time", line.time);
                        p.textContent = line.text;
                        container.appendChild(p);
                    });
                }

                function updateLyrics(currentTime, lyricLines) {
                    lyricLines.forEach(function (line) {
                        var p = document.querySelector('p[data-time="' + line.time + '"]');
                        if (p) {
                            if (currentTime >= line.time && (line.time + 4) > currentTime) {
                                p.style.color = 'red';
                            } else {
                                p.style.color = '';
                            }
                        }
                    });
                }
            </script>
        </div>
HTML;
    }
}
?>
