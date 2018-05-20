import mdcAutoInit from '@material/auto-init';
import {MDCTopAppBar} from '@material/top-app-bar';
import {MDCTemporaryDrawer} from '@material/drawer';

mdcAutoInit.register('MDCTopAppBar', MDCTopAppBar);


let drawer = new MDCTemporaryDrawer(document.querySelector('.mdc-drawer--temporary'));
document.querySelector('.mdc-top-app-bar__navigation-icon').addEventListener('click', () => drawer.open = true);
