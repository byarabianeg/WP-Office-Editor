document.addEventListener("DOMContentLoaded", () => {
    const editors = document.querySelectorAll(".wp-office-editor-area");

    editors.forEach((area) => {
        DecoupledEditor.create(area)
            .then(editor => {
                const toolbarContainer = area.parentElement.querySelector(".toolbar-container");
                toolbarContainer.appendChild(editor.ui.view.toolbar.element);

                window.wpOfficeEditors = window.wpOfficeEditors || {};
                window.wpOfficeEditors[area.id] = editor;
            })
            .catch(error => console.error("CKEditor Error:", error));
    });
});
