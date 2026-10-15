/* Unit 9: Pointers
 * (See also structure_basic.c/pointer_to_structure.c in this folder.)
 * Pointers and structures together: a function that fills a structure
 * via a pointer argument. */
#include <stdio.h>

struct Point {
    int x, y;
};

void setPoint(struct Point *p, int x, int y) {
    p->x = x;
    p->y = y;
}

int main(void) {
    struct Point p1;
    setPoint(&p1, 5, 9);

    printf("Point set via pointer: (%d, %d)\n", p1.x, p1.y);
    return 0;
}
