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
            </td>
        </tr>
        <tr>
            <td>
                <textarea class="<?=$term?>"></textarea>
            </td>
            <td>
                <input class="delete" type="button" value="Delete" data-term="<?=$term?>"/><br/>
                <input class="create" type="button" value="Create" data-term="<?=$term?>"/>
            </td>
        </tr>
    </tbody>
</table>
