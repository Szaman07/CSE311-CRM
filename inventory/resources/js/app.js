import './bootstrap';
import './sale-cart';
import { nexaFetch } from './nexa-api';

window.CRM = { fetch: nexaFetch };
