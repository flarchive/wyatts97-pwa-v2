import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';

export default class PWALogoUploadButton extends Button {
  static initAttrs(attrs) {
    super.initAttrs(attrs);
    attrs.name = `pwa-icon-${attrs.size}x${attrs.size}`;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.loading = false;

    this.$('input').on('change', (e) => {
      this.uploadFile(e.target.files[0]);
      e.target.value = '';
    });
  }

  view(vnode) {
    const hasImage = app.data.settings['askvortsov-pwa.icon_' + this.attrs.size + '_path'];

    if (hasImage) {
      return (
        <div>
          <p>
            <img src={app.forum.attribute(this.attrs.name + 'Url')} alt="" />
          </p>
          <p>
            {Button.component(
              {
                className: 'Button',
                onclick: this.remove.bind(this),
                loading: this.loading,
              },
              app.translator.trans('core.admin.upload_image.remove_button')
            )}
          </p>
        </div>
      );
    }

    return (
      <div>
        {Button.component(
          {
            className: 'Button',
            onclick: this.triggerUpload.bind(this),
            loading: this.loading,
          },
          app.translator.trans('core.admin.upload_image.upload_button')
        )}
        <input type="file" accept="image/*" name={this.attrs.name} style="display:none" />
      </div>
    );
  }

  triggerUpload() {
    this.$('input').trigger('click');
  }

  uploadFile(file) {
    if (!file) return;

    const body = new FormData();
    body.append(this.attrs.name, file);

    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'POST',
        url: this.resourceUrl(),
        body,
        serialize: (raw) => raw,
      })
      .then(() => {
        app.data.settings['askvortsov-pwa.icon_' + this.attrs.size + '_path'] = '1';
        app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.upload_image.upload_success'));
      })
      .catch(() => {
        app.alerts.show({ type: 'error' }, app.translator.trans('core.admin.upload_image.upload_error'));
      })
      .finally(() => {
        this.loading = false;
        m.redraw();
      });
  }

  remove() {
    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'DELETE',
        url: this.resourceUrl(),
      })
      .then(() => {
        app.data.settings['askvortsov-pwa.icon_' + this.attrs.size + '_path'] = '';
        app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.upload_image.remove_success'));
      })
      .catch(() => {
        app.alerts.show({ type: 'error' }, app.translator.trans('core.admin.upload_image.remove_error'));
      })
      .finally(() => {
        this.loading = false;
        m.redraw();
      });
  }

  resourceUrl() {
    return app.forum.attribute('apiUrl') + '/pwa/logo/' + this.attrs.size;
  }
}
