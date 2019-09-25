<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
 * Copyright (c) since 2019 Scavix Software GmbH & Co. KG
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
 ?>
<table class="new_string <?=$term?>">
    <thead>
        <tr>
            <td colspan="2"><?=$term?></td>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan="2">
                Hits: <?=$hits?>, Last request: <?=$last_hit?>
<?php if( count($data) > 0 ): echo "<br/>"; foreach( $data as $k=>$v ): ?>
<span title="Sample: <?=$v?>" class="termdata" onclick="$(this).closest('table').find('textarea').insertAtCaret($(this).text());"><?=$k?></span>
<?php endforeach; endif; ?>
            </td>
        </tr>
        <tr>
            <td>
                <textarea class="<?=$term?>"><?=$default_val?></textarea>
            </td>
            <td>
                <input class="create" type="button" value="Create" data-term="<?=$term?>"/><br/>
                <input class="delete" type="button" value="Delete" data-term="<?=$term?>"/>
            </td>
        </tr>
    </tbody>
</table>
