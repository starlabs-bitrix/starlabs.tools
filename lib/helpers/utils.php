<?php
namespace Starlabs\Tools\Helpers;

/**
 * Утилиты
 */
class Utils
{
	/**
	 * Склоняет существительное с числительным
	 *
	 * @param integer $number Число
	 * @param array $cases Варианты существительного в разных падежах и числах. Пример: array('комментарий', 'комментария', 'комментариев')
	 * @param boolean $incNum Добавить само число в результат
	 * @return string
	 */
    public static function getNumEnding($number, $cases, $incNum = true)
    {
        $numberMod = intval(preg_replace('/[^0-9.,]/', '', $number)) % 100;
        if ($numberMod >= 11 && $numberMod <= 19) {
            $result = $cases[2];
        } else {
            $numberMod = $numberMod % 10;
            switch ($numberMod) {
                case 1:
                    $result = $cases[0];
                    break;
                case 2:
                case 3:
                case 4:
	                $result = $cases[1];
	                break;
	            default:
		            $result = $cases[2];
            }
        }

	    return $incNum ? $number . ' ' . $result : $result;
    }

	/**
	 * Обрезает текст, превышающий заданную длину
	 *
	 * @param string $text Текст
	 * @param array $config Конфигурация
	 * @return string
	 */
	public static function getEllipsis($text, $config = [])
	{
		$config = array_merge([
			'mode' => 'word',
			'count' => 255,
			'suffix' => '&hellip;',
			'stripTags' => true,
		], $config);

		if ($config['stripTags']) {
            $text = preg_replace([
                '/(\r?\n)+/',
                '/^(\r?\n)+/',
            ], [
                "\n",
                '',
            ], strip_tags($text));
        }

        if (strlen($text) > $config['count']) {
            $text = substr($text, 0, $config['count']);
            switch ($config['mode']) {
                case 'direct':
                    break;
                case 'word':
                    $word = '[^ \t\n\.,:]+';
                    $text = preg_replace('/(' . $word . ')$/D', '', $text);
                    break;
                case 'sentence':
                    $sentence = '[\.\!\?]+[^\.\!\?]+';
                    $text = preg_replace('/(' . $sentence . ')$/D', '', $text);
                    break;
            }

            $text = preg_replace('/[ \.,;]+$/D', '', $text) . $config['suffix'];
        }

        if ($config['stripTags']) {
            $text = nl2br($text);
        }
        return $text;
    }

    /**
     * Получает id видео youtube из строки со ссылкой
     *
     * @param $string
     * @return bool
     */

    public static function getYoutubeIDFromString($string)
    {
        if (!strlen($string) > 0) {
            return false;
        }
        $re = "/(?:youtube\\.com\\/(?:[^\\/]+\\/.+\\/|(?:v|e(?:mbed)?)\\/|.*[?&]v=)|youtu\\.be\\/)([^\"&?\\/ ]{11})/i";
        preg_match($re, $string, $matches);
        return $matches[1];
    }

    /**
     * Конвертирует кодировку
     *
     * @param mixed $data Данные для кодирования
     * @param string $from Исходная кодировка
     * @param string $to Требуемая кодировка
     * @return mixed
     */
    public static function convertCharset($data, $from, $to)
    {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = self::convertCharset($val, $from, $to);
            }
        } elseif (is_object($data)) {
            foreach ($data as $key => $val) {
                $data->$key = self::convertCharset($val, $from, $to);
            }
        } elseif (is_bool($data) || is_numeric($data)) {
	        return $data;
        } else {
	        $data = \Bitrix\Main\Text\Encoding::convertEncoding($data, $from, $to, $errorMessage);
        }

        return $data;
    }
}
