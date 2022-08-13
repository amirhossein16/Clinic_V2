<?php

namespace Core;
use App\Model\Section;

class Controller
{
    private string $viewpath = __DIR__ . '/../View/';
    public string $layout = 'main';

    public function show($path, $data = null)
    {
        if (!file_exists($this->viewpath . $path . '.php')) {
            echo 'something went wrong! Please try again';
        }
        else {
            if ($data !== null) {
                foreach ($data as $key => $value) {
                    $$key = $value;
                }
            }
            ob_start();
            include $this->viewpath . "layouts/$this->layout.php";
            $layout = ob_get_contents();
            ob_end_clean();

            ob_start();
            include $this->viewpath . $path . '.php';
            $content = ob_get_contents();
            ob_end_clean();
        }

        $content = $this->addComponents($content, $path, $data);

        $layout = $this->showToasts($layout);

        echo str_replace("{{Contact}}", $content, $layout);
    }

    /**
     * Include modules to content
     *
     * @param string $address
     * @param string|null $path optional
     * @param array|null $data optional
     *
     * @return string $content
     */
    public function includeByBuffer(string $address, string $path = null, array $data = null): string
    {
        ob_start();
        include $this->viewpath . $address;
        $module = ob_get_contents();
        ob_end_clean();
        return $module;
    }

    /**
     * Add Components to page
     * @param string full page
     * 
     * @return string full page with components
     */
    public function addComponents(string $content, string $path, array $data = null): string
    {
        if ($path != 'login' || $path != 'register') {
            $navbar = $this->includeByBuffer('components/Navbar.php', $path, $data);
            $content = str_replace("{{Navbar}}", $navbar, $content);
        }

        return $content;
    }

    /**
     * Add message from back to front
     *
     * @param string $type
     * @param string $message
     * @return void
     */
    public function addMessage(string $type, string $message)
    {
        $messages = [
            'type' => $type,
            'message' => $message
        ];
        if (!isset($_SESSION['messages'])) {
            $_SESSION['messages'] = [];
        }
        array_push($_SESSION['messages'], $messages);
    }

    /**
     * replace toasts whit {{Message}}
     *
     * @param string $layout
     * @return string
     */
    private function showToasts(string $layout): string
    {
        $toasts = '';
        $messages = $_SESSION['messages'];
        if (!empty($messages)) {
            foreach ($messages as $key => $value) {
                $toasts .= $this->includeByBuffer('components/messages/' . $value['type'] . 'Toast.php') . PHP_EOL;
                $toasts = str_replace("{{message}}", $value['message'], $toasts);
                unset($_SESSION['messages'][$key]);
            }
        }
        $toasts =
            "<div class='absolute top-2 inset-x-0 z-20 flex flex-col gap-2'>" .
            $toasts .
            "</div>" .
            PHP_EOL;

        return str_replace("{{Message}}", $toasts, $layout);
    }

    public function findSections(): array
    {
        $section = new Section;
        return $section->select(['*']);
    }

    public function uploadFile(string $target_upload, string $filename): bool|string
    {
        if (!file_exists('../Public/storage' . $target_upload)) {
            if (!file_exists('../Public/storage')) {
                mkdir('../Public/storage');
            }
            if (!file_exists('../Public/storage/' . $target_upload)) {
                mkdir('../Public/storage/' . $target_upload);
            }   
        }

        if (!move_uploaded_file($_FILES["file-upload"]["tmp_name"], "../Public/storage/$target_upload/$filename.jpg")) {
            return false;
        }

        return "storage/$target_upload/$filename.jpg";
    }
}
