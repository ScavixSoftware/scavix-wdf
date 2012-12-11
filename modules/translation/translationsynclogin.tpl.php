<form action="<?=buildQuery(current_page_class(),'Login')?>" method="post" class="login">
    <table>
        <thead>
            <tr>
                <td colspan="2">Login first</td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Username</td>
                <td align="right"><input name="username" type="text"/></td>
            </tr>
            <tr>
                <td>Password</td>
                <td align="right"><input name="password" type="password"/></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" align="right">
                    <a href="<?=buildQuery('','')?>"/>Back to app</a>
                    <input type="submit" value="Login"/>
                </td>
            </tr>
        </tfoot>
    </table>
</form>