import mdcAutoInit from '@material/auto-init/index';
import {MDCTopAppBar} from '@material/top-app-bar/index';
import {MDCDrawer} from '@material/drawer/index';
import {MDCRipple} from '@material/ripple/index';
import {MDCIconToggle} from '@material/icon-toggle/index';
import {MDCSnackbar} from '@material/snackbar/index';
import {MDCTextField} from '@material/textfield/index';
import {MDCTextFieldHelperText} from '@material/textfield/helper-text/index';
import {MDCTab, MDCTabBar} from '@material/tabs/index';
import {MDCSelect} from '@material/select/index';
import clipboard from 'clipboard-copy';
import {MDCFormField} from '@material/form-field/index';
import {MDCCheckbox} from '@material/checkbox/index';
import {MDCTextFieldIcon} from '@material/textfield/icon/index';
import {MDCList} from "@material/list/index";
import {MDCMenuSurface} from "@material/menu-surface/index";
import {MDCMenu} from "@material/menu/index";
import {MDCSwitch} from "@material/switch/index";

mdcAutoInit.register('MDCTopAppBar', MDCTopAppBar);
mdcAutoInit.register('MDCDrawer', MDCDrawer);
mdcAutoInit.register('MDCRipple', MDCRipple);
mdcAutoInit.register('MDCIconToggle', MDCIconToggle);
mdcAutoInit.register('MDCSnackbar', MDCSnackbar);
mdcAutoInit.register('MDCTextField', MDCTextField);
mdcAutoInit.register('MDCTab', MDCTab);
mdcAutoInit.register('MDCTabBar', MDCTabBar);
mdcAutoInit.register('MDCSelect', MDCSelect);
mdcAutoInit.register('MDCMenuSurface', MDCMenuSurface);
mdcAutoInit.register('MDCMenu', MDCMenu);
mdcAutoInit.register('MDCList', MDCList);
mdcAutoInit.register('MDCTextFieldHelperText', MDCTextFieldHelperText);
mdcAutoInit.register('MDCTextFieldIcon', MDCTextFieldIcon);
mdcAutoInit.register('MDCFormField', MDCFormField);
mdcAutoInit.register('MDCCheckbox', MDCCheckbox);
mdcAutoInit.register('MDCSwitch', MDCSwitch);

mdcAutoInit();

const drawerEl = document.querySelector('.mdc-drawer');
if ( drawerEl ) {
    const drawer = drawerEl.MDCDrawer;
    document.querySelector('.mdc-top-app-bar__navigation-icon').addEventListener('click', () => drawer.open = !drawer.open);
}

export async function handleCopy(el) {
    const snackBar = document.querySelector('.mdc-snackbar');
    el.addEventListener('MDCIconToggle:change', () => {
        clipboard(document.querySelector('.secret').innerText)
            .then(() => {
                snackBar.MDCSnackbar.labelText = 'Copied!';
                snackBar.MDCSnackbar.timeoutMs = 5000;
                snackBar.MDCSnackbar.open();
            }).catch((err) => {
                snackBar.MDCSnackbar.labelText = 'Your browser does not support this. Please copy it manually.';
                snackBar.MDCSnackbar.timeoutMs = 5000;
                snackBar.MDCSnackbar.open();
        });
        setTimeout(() => { el.MDCIconToggle.on = false; }, 1000);
    });
}
