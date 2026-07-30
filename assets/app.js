import './styles/app.css';
import { startStimulusApp } from '@symfony/stimulus-bundle';
import TableFilterController from './controllers/table_filter_controller.js';
import ModalController from './controllers/modal_controller.js';
import DashboardController from './controllers/dashboard_controller.js';
import ModuleFindingController from './controllers/module_finding_controller.js';

const app = startStimulusApp();
app.register('table-filter', TableFilterController);
app.register('modal', ModalController);
app.register('dashboard', DashboardController);
app.register('module-finding', ModuleFindingController);
