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
     * Walk an HTML fragment and add native lazy-loading + width/height to
     * every <img>. Width/height let the browser reserve space (no layout
     * shift); loading="lazy" + decoding="async" defer below-the-fold work
     * to the browser, which respects the HTTP cache so repeat visits skip
     * the network entirely. Dimensions are looked up via getimagesize() on
     * the local file and memoized for the request.
     */
    public function withImageDimensions($html)
    {
        if (empty($html) || stripos($html, '<img') === false) return $html;
        $self = $this;
        return preg_replace_callback(
            '/<img\b[^>]*>/i',
            function ($m) use ($self) {
                $tag = $m[0];
                $hasAnyW = (bool)preg_match('/\swidth\s*=/i', $tag);
                $hasAnyH = (bool)preg_match('/\sheight\s*=/i', $tag);
                $hasLoading = (bool)preg_match('/\sloading\s*=/i', $tag);
                $hasDecoding = (bool)preg_match('/\sdecoding\s*=/i', $tag);

                $inject = '';
                if (!$hasLoading)  $inject .= ' loading="lazy"';
                if (!$hasDecoding) $inject .= ' decoding="async"';

                if (!$hasAnyW || !$hasAnyH) {
                    $src = null;
                    if (preg_match('/\ssrc\s*=\s*"([^"]+)"|\ssrc\s*=\s*\'([^\']+)\'/i', $tag, $sm)) {
                        $src = !empty($sm[1]) ? $sm[1] : $sm[2];
                    }
                    if (!empty($src) && strpos($src, 'data:') !== 0) {
                        $size = $self->lookupImageSize($src);
                        if (!empty($size)) {
                            list($natW, $natH) = $size;
                            if ($natW > 0 && $natH > 0) {
                                if (!$hasAnyW && !$hasAnyH) {
                                    $inject .= ' width="' . $natW . '" height="' . $natH . '"';
                                } elseif (!$hasAnyH) {
                                    $existingW = preg_match('/\swidth\s*=\s*"(\d+)"|\swidth\s*=\s*\'(\d+)\'/i', $tag, $wm)
                                        ? (int)(!empty($wm[1]) ? $wm[1] : $wm[2]) : null;
                                    if ($existingW) {
                                        $h = (int)round($natH * ($existingW / $natW));
                                        if ($h > 0) $inject .= ' height="' . $h . '"';
                                    }
                                } elseif (!$hasAnyW) {
                                    $existingH = preg_match('/\sheight\s*=\s*"(\d+)"|\sheight\s*=\s*\'(\d+)\'/i', $tag, $hm)
                                        ? (int)(!empty($hm[1]) ? $hm[1] : $hm[2]) : null;
                                    if ($existingH) {
                                        $w = (int)round($natW * ($existingH / $natH));
                                        if ($w > 0) $inject .= ' width="' . $w . '"';
                                    }
                                }
                            }
                        }
                    }
                }

                if ($inject === '') return $tag;
                if (substr($tag, -2) === '/>') {
                    return substr($tag, 0, -2) . $inject . ' />';
                }
                return substr($tag, 0, -1) . $inject . '>';
            },
            $html
        );
    }

    public function lookupImageSize($src)
    {
        static $memo = array();
        if (array_key_exists($src, $memo)) return $memo[$src];
        $clean = preg_replace('/[\?#].*$/', '', $src);
        $local = $this->urlToLocalPath($clean);
        $size = null;
        if ($local !== null && is_file($local)) {
            $info = @getimagesize($local);
            if (is_array($info) && !empty($info[0]) && !empty($info[1])) {
                $size = array((int)$info[0], (int)$info[1]);
            }
        }
        // Remote URLs are skipped on purpose: the only ones in this site
        // are decorative badges/iframes, and a remote getimagesize() can
        // stall the render. Author can pin width/height in markup if needed.
        $memo[$src] = $size;
        return $size;
    }

    private function urlToLocalPath($src)
    {
        if ($src === '' || $src === null) return null;
        $domain = defined('DOMAIN') ? rtrim(DOMAIN, '/') : '';
        if ($domain && strpos($src, $domain) === 0) {
            $src = substr($src, strlen($domain));
        } elseif (strpos($src, '//') === 0) {
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
