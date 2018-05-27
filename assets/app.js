import mdcAutoInit from '@material/auto-init';
import {MDCTopAppBar} from '@material/top-app-bar';
import {MDCTemporaryDrawer} from '@material/drawer';
import {MDCRipple} from '@material/ripple';
import {MDCIconToggle} from '@material/icon-toggle';
import {MDCSnackbar} from '@material/snackbar';
import {MDCTextField} from '@material/textfield';
import {MDCTextFieldHelperText} from '@material/textfield/helper-text';
import {MDCTab, MDCTabBar} from '@material/tabs';
import {MDCSelect} from '@material/select';
import clipboard from 'clipboard-copy';
import {MDCFormField} from '@material/form-field';
import {MDCCheckbox} from '@material/checkbox';

mdcAutoInit.register('MDCTopAppBar', MDCTopAppBar);
mdcAutoInit.register('MDCTemporaryDrawer', MDCTemporaryDrawer);
mdcAutoInit.register('MDCRipple', MDCRipple);
mdcAutoInit.register('MDCIconToggle', MDCIconToggle);
mdcAutoInit.register('MDCSnackbar', MDCSnackbar);
mdcAutoInit.register('MDCTextField', MDCTextField);
mdcAutoInit.register('MDCTab', MDCTab);
mdcAutoInit.register('MDCTabBar', MDCTabBar);
mdcAutoInit.register('MDCSelect', MDCSelect);
mdcAutoInit.register('MDCTextFieldHelperText', MDCTextFieldHelperText);
mdcAutoInit.register('MDCFormField', MDCFormField);
mdcAutoInit.register('MDCCheckbox', MDCCheckbox);

mdcAutoInit();

const drawerEl = document.querySelector('.mdc-drawer--temporary');
if ( drawerEl ) {
    const drawer = drawerEl.MDCTemporaryDrawer;
    document.querySelector('.mdc-top-app-bar__navigation-icon').addEventListener('click', () => drawer.open = true);
}

export async function handleCopy(el) {
    const snackBar = document.querySelector('.mdc-snackbar');
    el.addEventListener('MDCIconToggle:change', () => {
        clipboard(document.querySelector('.secret').innerText)
            .then(() => {
                snackBar.MDCSnackbar.show({message: 'Copied!', timeout: 5000});
            }).catch((err) => {
            snackBar.MDCSnackbar.show({message: 'Your browser does not support this. Please copy it manually.', timeout: 5000});
        });
        setTimeout(() => { el.MDCIconToggle.on = false; }, 1000);
    });
}
