import './styles/app.css';
import { startStimulusApp } from '@symfony/stimulus-bundle';
import TableFilterController from './controllers/table_filter_controller.js';

const app = startStimulusApp();
app.register('table-filter', TableFilterController);
