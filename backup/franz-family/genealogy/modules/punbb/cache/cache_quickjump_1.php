<?php

if (!defined('PUN')) exit;
define('PUN_QJ_LOADED', 1);

?>				<form id="qjump" method="get" action="<?php genurl('viewforum.php',true,true)?>">
					<div><label><?php echo $lang_common['Jump to'] ?>

					<br /><select name="id" onchange="window.location=('<?php genurl('viewforum.php',true,true)?>&id='+this.options[this.selectedIndex].value)">
						<optgroup label="Test category">
							<option value="1"<?php echo ($forum_id == 1) ? ' selected="selected"' : '' ?>>Test forum</option>
					</optgroup>
					</select>
					<input type="submit" value="<?php echo $lang_common['Go'] ?>" accesskey="g" />
					</label></div>
				</form>
