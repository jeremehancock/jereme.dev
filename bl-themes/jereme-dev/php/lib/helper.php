<?php
class Helper
{
	private $useCDN = false;

	function __construct($useCDN=false)
	{
		$this->useCDN = $useCDN;
	}

	public function cdn_that_image($image, $width)
    {
		if( ! $this->useCDN)  return $image;
		if ( 0 === strpos( $image, '//' ) ) {
			$image = 'https:' . $image;
		}
		$image_url_parts = @parse_url( $image );

		if ( ! is_array( $image_url_parts ) || empty( $image_url_parts['host'] ) || empty( $image_url_parts['path'] ) ) return $image;
		if( strpos( $image_url_parts['host'], 'localhost') !== false || strpos( $image_url_parts['host'], '127.0.0.1') !== false) return $image;

		$image_host_path = $image_url_parts['host'] . $image_url_parts['path'];

		$cdn_url  = "https://cdn.meln.top/";
		$cdn_url .= 'width/' . $width . '/n/'. $image_host_path; //resize image, keep proportions

        return $cdn_url;
    }

	public function cdn_cover_image($image, $width, $height)
    {
		if( ! $this->useCDN)  return $image;
		if ( 0 === strpos( $image, '//' ) ) {
			$image = 'https:' . $image;
		}
		$image_url_parts = @parse_url( $image );

		if ( !is_array( $image_url_parts ) || empty( $image_url_parts['host'] ) || empty( $image_url_parts['path'] ) ) return $image;
		if( strpos( $image_url_parts['host'], 'localhost') !== false || strpos( $image_url_parts['host'], '127.0.0.1') !== false) return $image;

		$image_host_path = $image_url_parts['host'] . $image_url_parts['path'];

		$cdn_url  = "https://cdn.meln.top";
		$cdn_url .= '/cover/' . $width .'x'.$height. '/n/'. $image_host_path; //resize image, keep proportions

        return $cdn_url;
    }

    public function get_thumb()
    {
        global $page;
        $first_img = $page->thumbCoverImage();
        if (empty($first_img)) {
            preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $page->content(), $matches);
            if (isset($matches[1][0])) {
                $first_img = $matches[1][0];
            }
        }
        return $first_img;
    }


    public function previousKey()
    {
        global $page;
		global $pages;
		if($page->isStatic()) return false;
        $currentKey = $page->key();
        $keys = $pages->getPublishedDB(true);
        $position = array_search($currentKey, $keys) + 1;
        if (isset($keys[$position])) {
            return $keys[$position];
        }
        return false;
    }

    public function nextKey()
    {
        global $page;
		global $pages;
		if($page->isStatic()) return false;
        $currentKey = $page->key();
        $keys = $pages->getPublishedDB(true);
        $position = array_search($currentKey, $keys) - 1;
        if (isset($keys[$position])) {
            return $keys[$position];
        }
        return false;
    }

    public function head_description()
    {
        global $site;
        global $WHERE_AM_I;
        global $page;
        global $url;

        $description = $site->description();

        if ($WHERE_AM_I == 'page') {
            $description = $page->description();
            if (empty($description)) {
                $cont = str_replace('<', ' <', $page->content(false));
                $cont = html_entity_decode($cont, ENT_QUOTES | ENT_HTML5, "UTF-8");
                $description = $this->truncate2nearest_word(Text::removeHTMLTags($cont), 168, '...'); //max size for SEO 2019 (150-170).
                $description = trim($description);
            }
        } elseif ($WHERE_AM_I == 'category') {
            try {
                $categoryKey = $url->slug();
                $category = new Category($categoryKey);
                $description = $category->description();
            }
			catch (Exception $e) {
				// description from the site
            }
        }
        return '<meta name="description" content="' . $description . '">' . PHP_EOL;
    }

	public function description()
    {
        global $site;
        global $WHERE_AM_I;
        global $url;

        $description = $site->description();

        if ($WHERE_AM_I == 'category') {
            try {
                $categoryKey = $url->slug();
                $category = new Category($categoryKey);
                $description = $category->description();
            }
			catch (Exception $e) {
				// description from the site
            }
        }
        return $description;
    }


	public function getPageDescription($page)
    {
		$description = $page->description();
		if (empty($description)) {
			$content = str_replace('<', ' <', $page->content(false));
			$content = html_entity_decode($content);
			$description = Text::truncate(Text::removeHTMLTags($content), 150);
			$description = trim(preg_replace('/\s+/', ' ', $description));//remove repeated spaces
		}
		return $description;
	}

	/**
	 * Highlight phrase in text and return snippet
	 * @param string $text
	 * @param string $phrase
	 * @param int $radius
	 * @param string $ending
	 * @return string
	 */
	public function snippet($text, $phrase, $radius = 100, $ending = '...') {

		$phrase =trim(preg_replace('/\s+/', ' ',$phrase));
		$words = join('|', explode(' ', preg_quote($phrase)));

		$phraseLen = strlen($phrase);
		if ($radius < $phraseLen) {
			$radius = $phraseLen;
		}

		$phrases = explode (' ',$phrase);
		$pos = -1;
		foreach ($phrases as $phrase) {
			$pos = strpos(strtolower($text), strtolower($phrase));
			if ($pos > -1) break;
		}

		$startPos = 0;
		if ($pos > $radius) {
			$startPos = $pos - $radius;
		}

		$textLen = strlen($text);

		$endPos = $pos + $phraseLen + $radius;
		if ($endPos >= $textLen) {
			$endPos = $textLen;
		}

		$excerpt = substr($text, $startPos, $endPos - $startPos);
		if ($startPos != 0) {
			$excerpt = substr_replace($excerpt, $ending, 0, $phraseLen);
		}

		if ($endPos != $textLen) {
			$excerpt = substr_replace($excerpt, $ending, -$phraseLen);
		}

		$excerpt= preg_replace('#'.$words.'#iu', "<strong>\$0</strong>", $excerpt);
		return $excerpt;
	}



	public function slogan()
    {
        global $site;
        global $WHERE_AM_I;
        global $url;
        $slogan = $site->slogan();
        if ($WHERE_AM_I == 'category') {
            try {
                $categoryKey = $url->slug();
                $category = new Category($categoryKey);
                $slogan = $category->name();
            }
			catch (Exception $e) {
				// slogan from the site
            }
        }
        return $slogan;
    }

	public function limit_text_words($text, $limit, $ending = '...') {
		if (str_word_count($text, 0) > $limit) {
			$words = str_word_count($text, 2);
			$pos = array_keys($words);
			$text = substr($text, 0, $pos[$limit]) . $ending;
		}
		return $text;
    }

	public function truncate2nearest_word($text, $limit, $ending = '...') {
		$text = str_replace('  ', ' ', $text); // replace repeated whitespace
		$text = substr($text, 0, strrpos(substr($text, 0, $limit), ' '));
		$text = trim($text);
		$text .= $ending;
		return $text;
	}

    public function content2excerpt($cont,  $limit=250, $ending = '...'  )
    {
        $cont = str_replace('<', ' <', $cont);
        $cont = html_entity_decode($cont, ENT_QUOTES | ENT_HTML5, "UTF-8");
        $descr = $this->truncate2nearest_word(Text::removeHTMLTags($cont), $limit, $ending);
        $descr = trim($descr);
        return $descr;
    }

    /**
     * Walk an HTML fragment and inject width/height attributes into every
     * <img> tag that doesn't already have them, so the browser can reserve
     * the correct space and lazy-loading doesn't cause layout shift.
     * Dimensions are looked up via getimagesize() (local files preferred,
     * remote URLs fall back to an HTTP fetch) and cached on disk so the
     * lookup is a one-time cost per image URL.
     */
    public function withImageDimensions($html)
    {
        if (empty($html) || stripos($html, '<img') === false) return $html;
        $self = $this;
        return preg_replace_callback(
            '/<img\b[^>]*>/i',
            function ($m) use ($self) {
                $tag = $m[0];
                $existingW = preg_match('/\swidth\s*=\s*"(\d+)"|\swidth\s*=\s*\'(\d+)\'/i', $tag, $wm)
                    ? (int)(!empty($wm[1]) ? $wm[1] : $wm[2]) : null;
                $existingH = preg_match('/\sheight\s*=\s*"(\d+)"|\sheight\s*=\s*\'(\d+)\'/i', $tag, $hm)
                    ? (int)(!empty($hm[1]) ? $hm[1] : $hm[2]) : null;
                $hasAnyW = (bool)preg_match('/\swidth\s*=/i', $tag);
                $hasAnyH = (bool)preg_match('/\sheight\s*=/i', $tag);
                if ($hasAnyW && $hasAnyH) return $tag;
                // Pull src from either src or data-src
                $src = null;
                if (preg_match('/\ssrc\s*=\s*"([^"]+)"|\ssrc\s*=\s*\'([^\']+)\'/i', $tag, $sm)) {
                    $src = !empty($sm[1]) ? $sm[1] : $sm[2];
                } elseif (preg_match('/\sdata-src\s*=\s*"([^"]+)"|\sdata-src\s*=\s*\'([^\']+)\'/i', $tag, $sm)) {
                    $src = !empty($sm[1]) ? $sm[1] : $sm[2];
                }
                if (empty($src)) return $tag;
                if (strpos($src, 'data:') === 0) return $tag;
                $size = $self->getCachedImageSize($src);
                if (empty($size)) return $tag;
                list($natW, $natH) = $size;
                if ($natW <= 0 || $natH <= 0) return $tag;
                // Decide which dimensions to write so the browser ends up
                // with a correct aspect ratio. If one side is already
                // pinned by the author, scale the other to match the
                // natural ratio rather than emitting raw natural pixels.
                $inject = '';
                if (!$hasAnyW && !$hasAnyH) {
                    $inject .= ' width="' . $natW . '" height="' . $natH . '"';
                } elseif (!$hasAnyH && $existingW !== null) {
                    $h = (int)round($natH * ($existingW / $natW));
                    if ($h > 0) $inject .= ' height="' . $h . '"';
                } elseif (!$hasAnyW && $existingH !== null) {
                    $w = (int)round($natW * ($existingH / $natH));
                    if ($w > 0) $inject .= ' width="' . $w . '"';
                } else {
                    // One side is set in non-pixel units (e.g. "100%") —
                    // can't compute the other safely without layout, skip.
                    return $tag;
                }
                if ($inject === '') return $tag;
                // Insert the new attrs just before the closing > (or />).
                if (substr($tag, -2) === '/>') {
                    return substr($tag, 0, -2) . $inject . ' />';
                }
                return substr($tag, 0, -1) . $inject . '>';
            },
            $html
        );
    }

    public function getCachedImageSize($src)
    {
        static $cache = null;
        static $dirty = false;
        $cacheFile = PATH_TMP . 'image-dimensions.json';
        if ($cache === null) {
            $cache = array();
            if (is_file($cacheFile)) {
                $raw = @file_get_contents($cacheFile);
                $decoded = @json_decode($raw, true);
                if (is_array($decoded)) $cache = $decoded;
            }
            register_shutdown_function(function () use (&$cache, &$dirty, $cacheFile) {
                if (!$dirty) return;
                if (!is_dir(PATH_TMP)) @mkdir(PATH_TMP, 0755, true);
                // Re-merge with whatever's on disk to avoid clobbering
                // entries written by a concurrent request.
                $existing = array();
                if (is_file($cacheFile)) {
                    $raw = @file_get_contents($cacheFile);
                    $decoded = @json_decode($raw, true);
                    if (is_array($decoded)) $existing = $decoded;
                }
                @file_put_contents(
                    $cacheFile,
                    json_encode(array_merge($existing, $cache)),
                    LOCK_EX
                );
            });
        }
        if (array_key_exists($src, $cache)) {
            $entry = $cache[$src];
            if (is_array($entry) && isset($entry[0], $entry[1])) return $entry;
            // Negative cache entry — stop retrying broken URLs forever.
            // Delete bl-content/tmp/image-dimensions.json to force a recheck.
            if ($entry === false) return null;
        }
        $size = $this->resolveImageSize($src);
        $cache[$src] = $size ? $size : false;
        $dirty = true;
        return $size;
    }

    private function resolveImageSize($src)
    {
        // Strip query string and fragment for local file resolution.
        $clean = preg_replace('/[\?#].*$/', '', $src);
        $local = $this->urlToLocalPath($clean);
        if ($local !== null && is_file($local)) {
            $info = @getimagesize($local);
            if (is_array($info) && !empty($info[0]) && !empty($info[1])) {
                return array((int)$info[0], (int)$info[1]);
            }
        }
        if (preg_match('#^https?://#i', $src) && ini_get('allow_url_fopen')) {
            // Cap remote lookups so a slow/unreachable host can't stall
            // the page render (PHP's default socket timeout is 60s).
            $oldTimeout = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', 3);
            $info = @getimagesize($src);
            ini_set('default_socket_timeout', $oldTimeout);
            if (is_array($info) && !empty($info[0]) && !empty($info[1])) {
                return array((int)$info[0], (int)$info[1]);
            }
        }
        return null;
    }

    private function urlToLocalPath($src)
    {
        if ($src === '' || $src === null) return null;
        // Drop our own site URL prefix so we treat it as a site-root path.
        $domain = defined('DOMAIN') ? rtrim(DOMAIN, '/') : '';
        if ($domain && strpos($src, $domain) === 0) {
            $src = substr($src, strlen($domain));
        } elseif (strpos($src, '//') === 0) {
            // Protocol-relative — only treat as local if the host matches.
            $parts = @parse_url('http:' . $src);
            $host  = isset($parts['host']) ? $parts['host'] : '';
            if ($domain && $host && strpos($domain, $host) !== false) {
                $src = isset($parts['path']) ? $parts['path'] : '';
            } else {
                return null;
            }
        }
        if (preg_match('#^https?://#i', $src)) return null;
        if ($src === '' || $src[0] !== '/') return null;
        return PATH_ROOT . ltrim($src, '/');
    }

	/**
	 * Summary of getRelated
	 * @param mixed $max
	 * @param mixed $similar
	 * @return array[]|string
	 */
    public function getRelated($max = 4, $similar = false)
    {
        global $WHERE_AM_I;
        global $page;
        if ($WHERE_AM_I == 'page') {
            $currentKey = $page->key();
            if (!$page->category()) return false;
            $currentCategory = getCategory($page->categoryKey());
            if (count($currentCategory->pages()) >= $max + 1) {
                $allCatPages = $currentCategory->pages();
				//remove curent page
                $allCatPages = array_diff($allCatPages, array($currentKey));

				//sort rest pages by similarity O(N**3)
                if ($similar) {
                    usort($allCatPages, function ($a, $b) use ($currentKey) {
                        similar_text($currentKey, $a, $percentA);
                        similar_text($currentKey, $b, $percentB);
                        return $percentA === $percentB ? 0 : ($percentA > $percentB ? -1 : 1);
                    });
                }
				//or just randomize
                else {
                    shuffle($allCatPages);
                }
                $related = array();
				try {
					for ($i = 0; $i < $max; $i++) {
						$item = new Page($allCatPages[$i]);
						if ($item->published()) {
							$related[] = $item;
						}
					}
				}
				catch(Exception $e) {
					//exception
				}
                return $related;
			}

		}
        return false;
    }
}
