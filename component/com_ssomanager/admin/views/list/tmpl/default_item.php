<?php
$return = base64_encode('index.php?option=com_ssomanager&task=entries&mode=' . $this->mode);
?>
<tr class="<?php echo "row".$this->item->index % 2; ?>">
	<td valign="top" align="center"><?php echo $this->item->cb ?></td>
	<td align="center"><?php echo $this->item->id ?></td>
	<td><a href="index.php?option=com_ssomanager&task=plugin.edit&mode=<?php echo $this->mode ?>&extension_id=<?php echo $this->item->id ?>&cid=<?php echo $this->item->id ?>&return=<?php echo $return ?>"><?php echo $this->item->name ?></a></td>
	<!--<td><a href="index.php?option=com_ssomanager&task=edit&mode=<?php echo $this->mode ?>&cid=<?php echo $this->item->id ?>"><?php echo $this->item->name ?></a></td>-->
	<td align="center"><?php  echo $this->item->state ?></td>
	<td align="center"><?php echo $this->item->ordering ?></td>
	<td align="center"><?php echo $this->item->type ?></td>
</tr>
