import Extend from 'flarum/common/extenders';
import PWAPage from './components/PWAPage';

// Flarum 2 bootExtensions only runs Admin/forum extenders from `extension.extend`, not `default`.
const extend = [new Extend.Admin('wyatts97-pwa-v2').page(PWAPage)];

export default extend;
export { extend };
