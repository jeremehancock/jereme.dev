<?php
declare(strict_types=1);
/*
 |  Redirects   Redirect your URLs to another ones
 |  @file       ./plugin.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.0 [0.1.0] - Alpha
 |
 |  @website    https://github.com/pytesNET/media
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 - 2020 pytesNET <info@pytes.net>
 */
    if(!defined("BLUDIT")) { die("Go directly to Jail. Do not pass Go. Do not collect 200 Cookies!"); }

    // Load Helper Functions
    require_once "functions.php";

    // Redirect Class
    class RedirectPlugin extends Plugin {
        const VERSION = "0.1.0";
        const STATUS = "Alpha";

        /*
         |  PLUGIN :: INIT
         |  @since  0.1.0
         */
        public function init(): bool {
            $this->dbFields = [
                "force-https"       => false,       // Force HTTPs
                "https-status"      => 301,         // Force HTTPs permanently or temporary
                "allow-external"    => false,       // Allow external redirects
                "redirects"         => [ ]          // All Redirects
            ];
            return true;
        }

        /*
         |  HOOK :: BEFORE ALL
         |  @since  0.1.0
         */
        public function beforeAll(): void {
            global $url;

            // Force HTTPs
            if($this->getValue("force-https") && ($_SERVER["HTTPS"] ?? "off") !== "on") {
                $redirect = str_replace("http:", "https:", DOMAIN_BASE) . ltrim($url->uri(), "/");
                if(!empty($_GET)) {
                    $redirect .= "?" . http_build_query($_GET);
                }

                if($this->getValue("https-status") === 301) {
                    header("HTTP/1.1 301 Moved Permanently");
                } else if($this->getValue("https-status") === 302) {
                    header("HTTP/1.1 302 Found (Moved Temporarily) ");
                }
                header("Location: {$redirect}");
                die();
            }

            // Check Redirects
            $redirects = $this->getValue("redirects");
            if(!array_key_exists($url->uri(), $redirects)) {
                return;
            }

            // Check Redirect
            [$to, $status] = array_values($redirects[$url->uri()]);
            if(strpos($to, "http") === 0) {
                if(!$this->getValue("allow-external")) {
                    return;
                }
            } else {
                $to = DOMAIN_BASE . ltrim($to, "/");
            }

            // Add Parameters
            if(!empty($_GET)) {
                $to .= "?" . http_build_query($_GET);
            }

            // Redirect
            if($status === 301) {
                header("HTTP/1.1 301 Moved Permanently");
            } else if($status === 302) {
                header("HTTP/1.1 302 Found (Moved Temporarily) ");
            }
            header("Location: {$to}");
            die();
        }

        /*
         |  PLUGIN :: FORM
         |  @since  0.1.0
         */
        public function form(): void {
            global $site;

            ?>
                <div class="form-group row">
                    <div class="col-3 pt-3">
                        <label for="force-https" class="text-right pt-2 mr-3" style="margin-top:0!important;"><?php paw_e("Force HTTPs"); ?></label>
                    </div>
                    <div class="col-9 pt-2 pl-4">
                        <div class="custom-control custom-checkbox">
                            <input type="hidden" name="force-https" value="false" />
                            <input id="force-https" type="checkbox" name="force-https" value="true" class="custom-control-input" <?php paw_checked($this->getValue("force-https")); ?> />
                            <label class="custom-control-label" for="force-https"><?php paw_e("Force HTTPs everywhere"); ?></label>
                        </div>
                        <span class="tip"><?php paw_e("Please check if SSL is activated on your domain BEFORE you enable this option."); ?></span>
                        <?php if(strpos($site->url(), "https") === false) { ?>
                            <span class="tip">
                                <?php paw_e("Your site URL does currently not start with"); ?> https://.
                                (<a href="<?php echo HTML_PATH_ADMIN_ROOT; ?>settings#advanced-tab"><?php echo paw_e("Change it here"); ?></a>)
                            </span>
                        <?php } ?>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-3 pt-3">
                        <label for="https-status" class="text-right pt-2 mr-3" style="margin-top:0!important;"><?php paw_e("HTTPs Status") ?></label>
                    </div>
                    <div class="col-9 pt-3">
                        <?php $val = $this->getValue("https-status"); ?>
                        <select id="https-status" name="https-status" class="custom-select">
                            <option value="301" <?php paw_selected($val, 301); ?>><?php echo "301 - " . paw__("Moved Permanently"); ?></option>
                            <option value="302" <?php paw_selected($val, 302); ?>><?php echo "302 - " . paw__("Moved Temporary"); ?></option>
                        </select>
                        <span class="tip"><?php paw_e("A permanently HTTP Status should be used for the most cases."); ?></span>
                    </div>
                </div>

                <div class="col-12 mt-4 mb-2"><hr /></div>

                <div class="form-group row">
                    <div class="col-3 pt-3">
                        <label for="allow-external" class="text-right pt-2 mr-3" style="margin-top:0!important;"><?php paw_e("External Redirects"); ?></label>
                    </div>
                    <div class="col-9 pt-2 pl-4">
                        <div class="custom-control custom-checkbox">
                            <input type="hidden" name="allow-external" value="false" />
                            <input id="allow-external" type="checkbox" name="allow-external" value="true" class="custom-control-input" <?php paw_checked($this->getValue("allow-external")); ?> />
                            <label class="custom-control-label" for="allow-external"><?php paw_e("Allow external Redirects"); ?></label>
                        </div>
                        <span class="tip"><?php paw_e("Pay attention to where you are forwarding."); ?></span>
                    </div>
                </div>

                <div class="col-12 mt-4 mb-3"><hr /></div>

                <div class="form-group row">
                    <div class="col-3 pt-3">
                        <label for="redirects" class="text-right pt-2 mr-3" style="margin-top:0!important;"><?php paw_e("Redirects"); ?></label>
                    </div>
                    <div class="col-9 pt-3">
                        <div class="input-group">
                            <input type="text" class="form-control" name="redirects[old]" value="" placeholder="/current/slug" />
                            <input type="text" class="form-control" name="redirects[new]" value="" placeholder="/forward/slug" />
                            <div class="input-group-append">
                                <span class="input-group-text border-0 p-0">
                                    <select name="redirects[status]" class="custom-select pr-4 rounded-0">
                                        <option value="301"><?php paw_e("Permanently"); ?></option>
                                        <option value="302"><?php paw_e("Temporary"); ?></option>
                                    </select>
                                </span>
                                <button id="addRedirect" class="btn btn-outline-secondary" type="button"><?php paw_e("Add Redirect"); ?></button>
                            </div>
                        </div>
                        <span class="tip"><?php paw_e("You just need to write the slug, the part after your site URL."); ?></span>
                        <div id="redirect-error" class="tip text-danger d-none"></div>
                        <div id="redirect-success" class="tip text-success d-none"><?php paw_e("The redirect rule has been added, but don't forget to save the page!"); ?></div>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-3 pt-3">
                        <label class="text-right pt-2 mr-3" style="margin-top:0!important;"><?php paw_e("Active Redirects"); ?></label>
                    </div>
                    <div class="col-9 pt-3">
                        <div id="redirects" class="redirects-list">
                            <?php
                                $count = 0;
                                $redirects = $this->getValue("redirects");
                                foreach($redirects AS $from => $data) {
                                    [$to, $status] = array_values($data);
                                    ?>
                                        <div class="redirect mb-2" data-redirect-number="<?php echo ++$count; ?>">
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="redirect[<?php echo $count; ?>][old]" value="<?php echo $from; ?>" placeholder="/current/slug" data-redirect="old" />
                                                <input type="text" class="form-control" name="redirect[<?php echo $count; ?>][new]" value="<?php echo $to; ?>" placeholder="/forward/slug" data-redirect="new" />
                                                <div class="input-group-append">
                                                    <span class="input-group-text border-0 p-0">
                                                        <select name="redirect[<?php echo $count; ?>][status]" class="custom-select pr-4 rounded-0" data-redirect="status">
                                                            <option value="301" <?php echo $status === 301? 'selected="selected"': ''; ?>><?php paw_e("Permanently"); ?></option>
                                                            <option value="302" <?php echo $status === 302? 'selected="selected"': ''; ?>><?php paw_e("Temporary"); ?></option>
                                                        </select>
                                                    </span>
                                                    <button class="btn btn-outline-danger" type="button" data-redirect="delete" style="height:34.4px;">
                                                        <span class="fa fa-trash pr-0"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                }
                            ?>
                        </div>
                    </div>
                </div>
            <?php
        }

        /*
         |  PLUGIN :: POST
         |  @since  0.1.0
         */
        public function post(): bool {
            $data = $_POST;

            // Check HTTPs
            if(isset($data["force-https"])) {
                $this->db["force-https"] = $data["force-https"] === "true";
            }
            if(isset($data["https-status"])) {
                $this->db["https-status"] = $data["https-status"] === "302"? 302: 301;
            }

            // External Redirects
            if(isset($data["allow-external"])) {
                $this->db["allow-external"] = $data["allow-external"] === "true";
            }

            // Redirects
            $redirects = [];
            if(isset($data["redirects"])) {
                foreach($data["redirect"] AS $redirect) {
                    [$from, $to, $status] = array_values($redirect);

                    // Sanitize FROM
                    $from = rtrim(trim(strtolower($from)), "/");
                    if(stripos($from, DOMAIN_BASE)) {
                        $from = str_replace(DOMAIN_BASE, "", $from);
                    } else if(stripos($from, DOMAIN)) {
                        $from = str_replace(DOMAIN, "", $from);
                    }

                    // Validate
                    if(strlen($from) === 0) {
                        continue;
                    }
                    if(strpos($from, "http") === 0) {
                        continue;
                    }

                    $from = strpos($from, "/") !== 0? "/" . $from: $from;
                    if(array_key_exists($from, $redirects)) {
                        continue;
                    }

                    // Sanitize TO
                    $to = rtrim(trim(strtolower($to)), "/");
                    if(stripos($to, DOMAIN_BASE)) {
                        $to = str_replace(DOMAIN_BASE, "", $to);
                    } else if(stripos($to, DOMAIN)) {
                        $to = str_replace(DOMAIN, "", $to);
                    }

                    // Validate
                    if(strlen($to) === 0) {
                        continue;
                    }
                    if(strpos($to, "http") === 0 && !$this->db["allow-external"]) {
                        continue;
                    } else if(strpos($to, "http") === false) {
                        $to = strpos($to, "/") !== 0? "/" . $to: $to;
                    }
                    if($from === $to) {
                        continue;
                    }

                    // Sanitize STATUS
                    $status = ($status === "301")? 301: 302;

                    // Add
                    $redirects[$from] = [$to, $status];
                }
            }
            $this->db["redirects"] = $redirects;
            return $this->save();
        }

        /*
         |  HOOK :: ADMIN SIDEBAR
         |  @since  0.1.0
         */
        public function adminSidebar(): string {
            return '<a href="' . HTML_PATH_ADMIN_ROOT . 'configure-plugin/RedirectPlugin" class="nav-link">'.paw__("Redirects").'</a>';
        }

        /*
         |  HOOK :: ADMIN BODY END
         |  @since  0.1.0
         */
        public function adminBodyEnd(): void {
            global $url;

            if($url->slug() !== "configure-plugin/RedirectPlugin") {
                return;
            }

            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    function showError(text) {
                        $("#redirect-error").text(text).removeClass("d-none");
                        $("#redirect-success").addClass("d-none");
                        return showAlert(text);
                    };

                    $("#addRedirect").click(function(event) {
                        event.preventDefault();

                        // Get List
                        var list = $("#redirects");

                        // Get Fields
                        var from = $("input[name='redirects[old]']");
                        var to = $("input[name='redirects[new]']");
                        var status = $("select[name='redirects[status]']");
                        var ext = $("input#allow-external");

                        // Sanitize FROM
                        var from_value = from.val().replace(/\/$/, "").trim().toLowerCase();
                        if(from_value.startsWith(DOMAIN_BASE)) {
                            from_value = from_value.replace(DOMAIN_BASE, "");
                        } else if(from_value.startsWith(DOMAIN)) {
                            from_value = from_value.replace(DOMAIN, "");
                        }

                        // ... starts with HTTP
                        if(from_value.startsWith("http")) {
                            return showError("<?php paw_e("The current slug must be just the page slug of your own website."); ?>");
                        }

                        // ... is empty
                        if(from_value.length === 0) {
                            return showError("<?php paw_e("The current slug field cannot be empty."); ?>");
                        }
                        if(!from_value.startsWith("/")) {
                            from_value = "/" + from_value;
                        }

                        // ... does already exist
                        list.find("input[data-redirect='old']").each(function() {
                            if(this.value === from_value) {
                                list = false;
                                return showError("<?php paw_e("A forward rule for the current slug does already exist."); ?>");
                            }
                        });
                        if(list === false) {
                            return;
                        }

                        // Sanitize TO
                        var to_value = to.val().replace(/\/$/, "").trim().toLowerCase();
                        if(to_value.startsWith(DOMAIN_BASE)) {
                            to_value = to_value.replace(DOMAIN_BASE, "");
                        } else if(to_value.startsWith(DOMAIN)) {
                            to_value = to_value.replace(DOMAIN, "");
                        }

                        // ... is empty
                        if(to_value.length === 0) {
                            return showError("<?php paw_e("The forward slug field cannot be empty."); ?>");
                        }

                        // ... starts with HTTP
                        if(to_value.startsWith("http") && !ext.prop("checked")) {
                            return showError("<?php paw_e("The forward slug must be just the page slug of your own website."); ?>");
                        } else if(!to_value.startsWith("http")) {
                            if(!to_value.startsWith("/")) {
                                to_value = "/" + to_value;
                            }
                        }

                        // ... is the same as the current url
                        if(to_value === from_value) {
                            return showError("<?php paw_e("The current and forward slug cannot be the same."); ?>");
                        }

                        // Sanitize STATUS
                        var status_value = status.val();
                        if(status_value !== "301" && status_value !== "302") {
                            return showError("<?php paw_e("The passed HTTP status is invalid."); ?>");
                        }

                        // Create Unique Number
                        var num = list.children().length;
                        while($(".redirect[data-redirect-number='" + num + "']").length > 0) {
                            num++;
                        }

                        // Create Form
                        var redirect = $("<div></div>", {
                            "class": "redirect mb-2",
                            "data-redirect-number": num,
                            html: '<div class="input-group">'
                                + '    <input type="text" class="form-control" name="redirect[' + num + '][old]" value="' + from_value + '" placeholder="' + from.attr("placeholder") + '" data-redirect="old" />'
                                + '    <input type="text" class="form-control" name="redirect[' + num + '][new]" value="' + to_value + '" placeholder="' + to.attr("placeholder") + '" data-redirect="new" />'
                                + '    <div class="input-group-append">'
                                + '        <span class="input-group-text border-0 p-0">'
                                + '            <select name="redirect[' + num + '][status]" class="custom-select pr-4 rounded-0" data-redirect="status">'
                                + '                <option value="301" ' + (status_value === "301"? "selected='selected'": "") + '>' + status.children().get(0).innerText + '</option>'
                                + '                <option value="302" ' + (status_value === "302"? "selected='selected'": "") + '>' + status.children().get(1).innerText + '</option>'
                                + '            </select>'
                                + '        </span>'
                                + '        <button class="btn btn-outline-danger" type="button" data-redirect="delete" style="height:34.4px;">'
                                + '            <span class="fa fa-trash pr-0"></span>'
                                + '        </button>'
                                + '    </div>'
                                + '</div>'
                        });
                        redirect.prependTo(list);

                        $("#redirect-error").addClass("d-none");
                        $("#redirect-success").removeClass("d-none");
                    });

                    // Delete Button
                    $(document).on("click", "button[data-redirect='delete']", function(event) {
                        event.preventDefault();
                        var redirect = this;
                        do  {
                            redirect = redirect.parentElement;
                        } while(redirect.classList.contains("redirect") === false);
                        redirect.parentElement.removeChild(redirect);
                    })
                });
            </script>
            <?php
        }
    }
