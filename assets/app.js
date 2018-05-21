import mdcAutoInit from '@material/auto-init';
import {MDCTopAppBar} from '@material/top-app-bar';
import {MDCTemporaryDrawer} from '@material/drawer';
import {MDCRipple} from '@material/ripple';
import {MDCIconToggle} from '@material/icon-toggle';
import {MDCSnackbar} from '@material/snackbar';
import clipboard from 'clipboard-copy';

mdcAutoInit.register('MDCTopAppBar', MDCTopAppBar);
mdcAutoInit.register('MDCTemporaryDrawer', MDCTemporaryDrawer);
mdcAutoInit.register('MDCRipple', MDCRipple);
mdcAutoInit.register('MDCIconToggle', MDCIconToggle);
mdcAutoInit.register('MDCSnackbar', MDCSnackbar);

mdcAutoInit();

const drawerEl = document.querySelector('.mdc-drawer--temporary');
if ( drawerEl ) {
    const drawer = drawerEl.MDCTemporaryDrawer;
    document.querySelector('.mdc-top-app-bar__navigation-icon').addEventListener('click', () => drawer.open = true);
}

const snackBar = document.querySelector('.mdc-snackbar');
const copySecret = document.querySelector('.copy-secret');
if ( copySecret ) {
    handleCopy(copySecret);
}

async function handleCopy(el) {
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

