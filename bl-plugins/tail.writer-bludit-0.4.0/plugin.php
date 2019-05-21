<?php
/*
 |  tail.writer 4 Bludit
 |  @file       ./plugin.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.4.0 [0.3.2] - Alpha
 |
 |  @website    https://github.com/pytesNET/tail.writer-bludit
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2019 pytesNET <info@pytes.net>
 */

	class PluginTailWriter extends Plugin{
		/*
		 |	LIST OF CONTROLLERS TO LOAD
		 */
		private $loadOnController = array(
			"new-content", "edit-content"
		);

		/*
		 |	HELPER :: SELECT OPTION
		 |	@version	0.4.0 [0.4.0]
		 */
		public function selected($value, $compare, $echo = true){
			if($this->getValue($value) == $compare){
				$selected = 'selected="selected"';
			} else {
				$selected = '';
			}
			if(!$echo){
				return $selected;
			}
			print($selected);
		}

		/*
		 |	HOOK :: INIT PLUGIN
		 |	@version	0.4.0 [0.4.0]
		 */
		public function init(){
			$this->dbFields = array(
				"markup"	=> "markdown",
				"locale"  	=> "en",
				"design"	=> "github"
			);
		}

		/*
		 |	HOOK :: PLUGIN SETTINGS
		 |	@version	0.4.0 [0.4.0]
		 */
		public function form(){
			global $L;

			ob_start();
			?>
				<div>
					<label><?php echo $L->get("Design"); ?></label>
					<select name="design">
						<option value="white" <?php $this->selected("design", "white"); ?>><?php echo $L->get("White") ?></option>
						<option value="dark" <?php $this->selected("design", "dark"); ?>><?php echo $L->get("Dark") ?></option>
						<option value="github" <?php $this->selected("design", "github"); ?>><?php echo $L->get("GitHub") ?></option>
					</select>
				</div>

				<div>
					<label><?php echo $L->get("Interface Language"); ?></label>
					<select name="locale">
						<option value="en" <?php $this->selected("locale", "en"); ?>><?php echo $L->get("English") ?></option>
						<option value="de" <?php $this->selected("locale", "de"); ?>><?php echo $L->get("German") ?></option>
					</select>
				</div>

				<div>
					<label><?php echo $L->get("Markup Language"); ?></label>
					<select name="markup">
						<option value="markdown" <?php $this->selected("markup", "markdown"); ?>>Markdown</option>
						<option value="textile" <?php $this->selected("markup", "textile"); ?>>Textile</option>
						<option value="bbcode" <?php $this->selected("markup", "bbcode"); ?>>BBCode</option>
					</select>
					<span class="tip"><?php echo $L->get("markup-support"); ?></span>
				</div>
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}

		/*
		 |	HOOK :: ADMIN HEAD
		 |	@version	0.4.0 [0.3.2]
		 */
		public function adminHead(){
			if(!in_array($GLOBALS["ADMIN_CONTROLLER"], $this->loadOnController)){
				return false;
			}

			ob_start();
			?>
				<link type="text/css" rel="stylesheet" href="<?php echo $this->htmlPath(); ?>css/tail.writer-<?php echo $this->getValue("design"); ?>.min.css" />
				<style type="text/css">
					.tail-writer{
						margin: 15px 0 0 0;
					}
					.tail-writer textarea#jseditor{
					    width: 100%;
						height: auto;
						min-height: 300px;
						max-height: inherit !important;
						padding: 20px !important;
						font-size: 16px;
						line-height: 24px;
						border: 1px solid #d1d5da;
						background-color: #f6f8fa;
					}
					.tail-writer textarea#jseditor:hover{
						border-color: #c1c5ca;
						background-color: #f6f8fa;
					}
					.tail-writer textarea#jseditor:focus{
						border-color: #2188ff;
						background-color: #ffffff;
					}
				</style>

				<script type="text/javascript" src="<?php echo $this->htmlPath(); ?>js/marked.min.js"></script>
				<script type="text/javascript" src="<?php echo $this->htmlPath(); ?>js/bbsolid.min.js"></script>
				<script type="text/javascript" src="<?php echo $this->htmlPath(); ?>js/textile.min.js"></script>
				<script type="text/javascript" src="<?php echo $this->htmlPath(); ?>js/tail.writer-full.min.js"></script>
				<script type="text/javascript">
					tail.writer.hook("init", "bludit", function(){
						this.e.editor.className = this.e.editor.className.replace("h-100", "");
						this.e.editor.focus();
					});
				</script>
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}

		/*
		 |	HOOK :: ADMIN HEAD
		 |	@version	0.4.0 [0.3.3]
		 */
		public function adminBodyEnd(){
			if(!in_array($GLOBALS["ADMIN_CONTROLLER"], $this->loadOnController)){
				return false;
			}

			ob_start();
			?>
				<script type="text/javascript">
					var WriterEditor = null;

					function editorInsertMedia(file){
						WriterEditor.writeContent("![" + WriterEditor.translate("imageTitle") + "](" + file + ")");
						$("#jsmediaManagerModal").on("hidden.bs.modal", function(){
							WriterEditor.e.editor.focus();
						});
					}

					function editorGetContent(){
						var content = WriterEditor.getContent();
						if(WriterEditor.con.markup === "textile"){
							content = textile(content);
						}
						if(WriterEditor.con.markup === "bbcode"){
							content = tail.BBSolid(content, {
				                prettyPrint: false,
				                showLineBreaks: false
				            });
						}
						return content;
					}

					<?php if(version_compare(BLUDIT_VERSION, "3.8.0", ">=")){ ?>
						document.addEventListener("DOMContentLoaded", function(){
							WriterEditor = tail.writer("#jseditor", {
								height: ["400px", "700px"],
								toolbar: "full",
								locale: "<?php echo $this->getValue("locale"); ?>",
								markup: "<?php echo $this->getValue("markup"); ?>",
							});
						});
					<?php } else { ?>
						document.addEventListener("DOMContentLoaded", function(){
							var area = document.querySelector("#jseditor"),
								text = document.createElement("textarea");
								text.id = "js-tail-writer";
								text.value = area.innerHTML;
								text.className = area.className;

							// Instance
							area.innerHTML = "";
							area.appendChild(text, area);
							WriterEditor = tail.writer("#jseditor", {
								height: ["400px", "700px"],
								toolbar: "full",
								locale: "<?php echo $this->getValue("locale"); ?>",
								markup: "<?php echo $this->getValue("markup"); ?>",
							});
						});
					<?php } ?>
				</script>
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
	}
