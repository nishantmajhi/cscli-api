/* Unit 11: Introduction to Graphics
 *
 * Concepts of Graphics, Graphics Initialization and Modes, Graphics
 * Functions - using the classic Borland-style graphics.h API that
 * this syllabus traditionally teaches with Turbo C / WinBGIm.
 *
 * NOTE: graphics.h is not part of standard C and modern compilers/
 * Linux do not ship it. To run this on Windows, install Turbo C++ or
 * the WinBGIm library for a modern compiler (e.g. MinGW); compile
 * with: gcc graphics_basic.c -lbgi -lgdi32 -lcomdlg32 -luuid -loleaut32 -lole32
 */
#include <graphics.h>
#include <stdio.h>

int main(void) {
    int gd = DETECT, gm;

    initgraph(&gd, &gm, NULL); /* graphics initialization */

    /* basic graphics functions */
    circle(300, 200, 50);
    rectangle(100, 100, 250, 250);
    line(0, 0, getmaxx(), getmaxy());
    outtextxy(150, 50, "C Graphics Demo");

    getch();
    closegraph(); /* shut down graphics mode */

    return 0;
}
